<?php

declare(strict_types=1);

namespace Live627\PhpCsFixer\CustomFixers;

use PhpCsFixer\AbstractFixer;
use PhpCsFixer\Fixer\RiskyFixerInterface;
use PhpCsFixer\FixerDefinition\CodeSample;
use PhpCsFixer\FixerDefinition\FixerDefinition;
use PhpCsFixer\FixerDefinition\FixerDefinitionInterface;
use PhpCsFixer\Tokenizer\Token;
use PhpCsFixer\Tokenizer\Tokens;

/**
 * Replaces array_key_exists() calls with equivalent isset() expressions.
 *
 * This fixer transforms:
 *
 * ```php
 * array_key_exists('foo', $array);
 * ```
 *
 * into:
 *
 * ```php
 * isset($array['foo']);
 * ```
 *
 * The fixer operates directly on the token stream and avoids generating
 * intermediate PHP code where possible. Argument boundaries are determined
 * with a single scan of the function call, allowing the replacement token
 * sequence to be assembled without repeated calls to
 * Tokens::getNextMeaningfulToken() or Tokens::getPrevMeaningfulToken().
 *
 * Nested expressions are supported for both the key and array arguments:
 *
 * ```php
 * array_key_exists(
 *     strtolower($key),
 *     getData($group, $index)
 * );
 * ```
 *
 * becomes:
 *
 * ```php
 * isset(getData($group, $index)[strtolower($key)]);
 * ```
 *
 * This fixer is risky because array_key_exists() and isset() are not
 * semantically equivalent. array_key_exists() returns true when a key
 * exists and its value is null, whereas isset() returns false in that
 * situation.
 *
 * Example:
 *
 * ```php
 * $data = ['foo' => null];
 *
 * array_key_exists('foo', $data); // true
 * isset($data['foo']);            // false
 * ```
 *
 * As a result, this fixer should only be used when the codebase guarantees
 * that the checked values cannot be null, or when the behavioral change is
 * intentional.
 */
final class ArrayKeyExistsToIssetFixer extends AbstractFixer
{
	public function getDefinition(): FixerDefinitionInterface
	{
		return new FixerDefinition(
			'Replaces array_key_exists() with isset().',
			[
				new CodeSample(
					"<?php\narray_key_exists('foo', \$array);\n"
				),
			],
			null,
			'Changes behavior when the array element exists and contains null.  Be sure to have unit tests that cover every possible permutation!.'
		);
	}

	public function getPriority(): int
	{
		return 0;
	}

	public function isRisky(): bool
	{
		return true;
	}

	public function isCandidate(Tokens $tokens): bool
	{
		return $tokens->isTokenKindFound(T_STRING);
	}

	/**
	 * Rewrites array_key_exists($key, $array) calls to isset($array[$key]).
	 *
	 * The fixer performs purely token-based transformations and avoids
	 * generating PHP source strings for replacements. Replacements are
	 * collected first and then applied in reverse order to prevent token
	 * indexes from shifting during iteration.
	 */
	protected function applyFix(\SplFileInfo $file, Tokens $tokens): void
	{
		$replacements = [];

		for ($index = 0, $count = $tokens->count(); $index < $count; ++$index) {
			$token = $tokens[$index];

			// We only care about calls to array_key_exists().
			if (!$token->isGivenKind(T_STRING) || $token->getContent() !== 'array_key_exists') {
				continue;
			}

			// Locate the opening parenthesis of the function call.
			$open = $tokens->getNextMeaningfulToken($index);

			if ($open === null || !$tokens[$open]->equals('(')) {
				continue;
			}

			// Find the matching closing parenthesis.
			$close = $tokens->findBlockEnd(Tokens::BLOCK_TYPE_PARENTHESIS_BRACE, $open);

			// Find the comma separating the two arguments.
			$args = $this->findArguments($tokens, $open + 1, $close - 1);

			// Skip malformed calls.
			if ($args === null) {
				continue;
			}

			// Build: isset(<array-expression>[<key-expression>])
			$replacementTokens = [
				new Token([T_ISSET, 'isset']),
				new Token('('),
			];

			// Copy the second argument (array expression).
			for ($i = $args['array_start']; $i <= $args['array_end']; ++$i) {
				$replacementTokens[] = $tokens[$i];
			}

			$replacementTokens[] = new Token('[');

			// Copy the first argument (key expression).
			for ($i = $args['key_start']; $i < $args['key_end']; ++$i) {
				$replacementTokens[] = $tokens[$i];
			}

			$replacementTokens[] = new Token(']');
			$replacementTokens[] = new Token(')');

			$replacements[] = [
				'start' => $index,
				'end' => $close,
				'tokens' => $replacementTokens,
			];

			//
			$index = $close + 1;
		}

		global $tt;

		$tt=-hrtime(true);

		// Apply replacements from the end of the file toward the beginning.
		// This prevents earlier replacements from invalidating token indexes
		// for later ones.
		foreach (array_reverse($replacements) as $replacement) {
			$tokens->overrideRange($replacement['start'], $replacement['end'], $replacement['tokens']);
		}

		$tt+=hrtime(true);
	}

	/**
	 * Finds the top-level comma separating the arguments of an
	 * array_key_exists() call and returns the boundaries of both arguments.
	 *
	 * This allows us to correctly split:
	 *
	 *     array_key_exists($key, $array)
	 *
	 * while ignoring commas contained inside nested expressions such as:
	 *
	 *     array_key_exists(foo($a, $b), $array)
	 *
	 * This method also tracks the first and last non-whitespace tokens of
	 * each argument while scanning.  Doing so avoids multiple calls to
	 * Tokens::getNextMeaningfulToken() and Tokens::getPrevMeaningfulToken(),
	 * reducing the number of token stream traversals required to build the
	 * replacement expression.
	 *
	 * Example:
	 *
	 *     array_key_exists($key, $data)
	 *                      ^----^ keyStart/keyEnd
	 *                            ^-----^ arrayStart/arrayEnd
	 *
	 * @param Tokens $tokens Token collection.
	 * @param int $start First token inside the argument list.
	 * @param int $end Last token inside the argument list.
	 *
	 * @return array{
	 *     comma: int,
	 *     key_start: int,
	 *     key_end: int,
	 *     array_start: int,
	 *     array_end: int
	 * }|null Returns null if no top-level comma is found.
	 */
	private function findArguments(Tokens $tokens, int $start, int $end): ?array
	{
		$depth = 0;
		$comma = null;

		$key_start = null;
		$key_end = null;
		$array_start = null;
		$array_end = null;

		for ($i = $start; $i <= $end; ++$i) {
			$token = $tokens[$i];

			// Track argument boundaries while scanning so we can avoid
			// subsequent getNextMeaningfulToken() and getPrevMeaningfulToken()
			// calls when constructing the replacement.
			if (!$token->isWhitespace()) {
				if ($comma === null) {
					$key_start ??= $i;
					$key_end = $i;
				} else {
					$array_start ??= $i;
					$array_end = $i;
				}
			}

			$content = $token->getContent();

			if ($content === '(' || $content === '[' || $content === '{') {
				++$depth;
			} elseif ($content === ')' || $content === ']' || $content === '}') {
				--$depth;
			} elseif ($depth === 0 && $content === ',') {
				$comma = $i;
			}
		}

		if ($comma === null) {
			return null;
		}

		return [
			'comma' => $comma,
			'key_start' => $key_start,
			'key_end' => $key_end,
			'array_start' => $array_start,
			'array_end' => $array_end,
		];
	}
}
