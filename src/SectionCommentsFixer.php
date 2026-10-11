<?php

/**
 * Simple Machines Forum (SMF)
 *
 * @package SMF
 * @author Simple Machines https://www.simplemachines.org
 * @copyright 2025 Simple Machines and individual contributors
 * @license https://www.simplemachines.org/about/smf/license.php BSD
 *
 * @version 3.0 Alpha 3
 */

declare(strict_types=1);

namespace Live627\PhpCsFixer\CustomFixers;

use PhpCsFixer\AbstractFixer;
use PhpCsFixer\Fixer\WhitespacesAwareFixerInterface;
use PhpCsFixer\FixerDefinition\CodeSample;
use PhpCsFixer\FixerDefinition\FixerDefinition;
use PhpCsFixer\FixerDefinition\FixerDefinitionInterface;
use PhpCsFixer\Tokenizer\CT;
use PhpCsFixer\Tokenizer\Token;
use PhpCsFixer\Tokenizer\Tokens;

/**
 * Inserts sectioning comments.
 *
 * Every class, interface, trait and enum gets its own set of section comments,
 * placed before the first element of each kind.  Anonymous classes are left
 * alone.
 *
 * Section comments (new or existing) are aligned with the method declaration,
 * one indentation level deeper than the class.  Whitespace is normalized so
 * that one blank line always goes after the section comment, and one before
 * it only if it's not immediately after the opening brace.
 *
 * @author Jon Stovell
 * @author John Rayes <live627@gmail.com>
 *
 * @no-named-arguments Parameter names are not covered by the backward compatibility promise.
 */
final class SectionCommentsFixer extends AbstractFixer implements WhitespacesAwareFixerInterface
{
	/*****************
	 * Class constants
	 *****************/

	/**
	 * The title of the section comment for each kind of class element.
	 */
	private const TITLES = [
		'case' => 'Enum cases',
		'const' => 'Class constants',
		'public_property' => 'Public properties',
		'public_static_property' => 'Public static properties',
		'internal_property' => 'Internal properties',
		'internal_static_property' => 'Internal static properties',
		'public_method' => 'Public methods',
		'public_static_method' => 'Public static methods',
		'internal_method' => 'Internal methods',
		'internal_static_method' => 'Internal static methods',
	];

	/*********************
	 * Internal properties
	 *********************/

	private string $section_comment_regex;

	/****************
	 * Public methods
	 ****************/

	public function __construct()
	{
		parent::__construct();

		$titles = array_map(
			static fn(string $title): string => preg_quote($title, '/'),
			array_values(self::TITLES),
		);

		$this->section_comment_regex = '/^\/[*\s]+(?:' . implode('|', $titles) . ')[*\s]+\/\z/';
	}

	public function getName(): string
	{
		return 'SMF/section_comments';
	}

	public function getDefinition(): FixerDefinitionInterface
	{
		return new FixerDefinition(
			'Inserts sectioning comments. This is meant to be used in combination with the `ordered_class_elements` rule.',
			[
				new CodeSample(<<<'END'
					<?php

					class Foo
					{
						/**
						 *
						 */
						const MY_CONSTANT = 1;

						/**
						 *
						 */
						public string $a = '';

						/**
						 *
						 */
						protected string $b = '';

						/**
						 *
						 */
						private string $c = '';

						/**
						 *
						 */
						public static string $d = '';

						/**
						 *
						 */
						protected static string $e = '';

						/**
						 *
						 */
						private static string $f = '';

						/**
						 *
						 */
						public function method1(): void {}

						/**
						 *
						 */
						protected function method2(): void {}

						/**
						 *
						 */
						private function method3(): void {}

						/**
						 *
						 */
						public static function method4(): void {}

						/**
						 *
						 */
						protected static function method5(): void {}

						/**
						 *
						 */
						private static function method6(): void {}
					}

					END),
			],
		);
	}

	/**
	 * Returns the priority of the fixer.
	 *
	 * The default priority is 0 and higher priorities are executed first.
	 *
	 * Must run after OrderedClassElementsFixer.
	 */
	public function getPriority(): int
	{
		return 54;
	}

	public function isCandidate(Tokens $tokens): bool
	{
		return $tokens->isAnyTokenKindsFound(Token::getClassyTokenKinds());
	}

	/******************
	 * Internal methods
	 ******************/

	protected function applyFix(\SplFileInfo $file, Tokens $tokens): void
	{
		// Find the elements of every class (anonymous ones are left out), and
		// the section comments that are already there. Whatever is not reused
		// below will be removed.
		[$classes, $existing] = $this->scan($tokens);

		// The first element of each kind in a class gets the section comment.
		$slices = [];

		foreach ($classes as $elements) {
			$seen = [];

			foreach ($elements as [$index, $kind]) {
				if ($kind === 'case' || $kind === 'const') {
					$type = $kind;

					if (isset($seen[$type])) {
						continue;
					}

					// Only the first element of a kind needs to be examined.
					[$start] = $this->scanModifiers($tokens, $index);
				} else {
					[$start, $visibility, $is_static] = $this->scanModifiers($tokens, $index);

					$type = $visibility . ($is_static ? '_static' : '') . '_' . $kind;

					if (isset($seen[$type])) {
						continue;
					}
				}

				$seen[$type] = true;

				$this->placeSectionComment($tokens, $start, $type, $existing, $slices);
			}
		}

		// Any existing section comments that are no longer wanted?
		foreach (array_keys($existing) as $key) {
			$tokens->clearAt($key);

			// If necessary, also delete whitespace.
			if (isset($tokens[$key + 1]) && $tokens[$key + 1]->isWhitespace()) {
				$tokens->clearAt($key + 1);
			}
		}

		// Insert new section comments.
		if ($slices !== []) {
			$tokens->insertSlices($slices);
		}
	}

	/**
	 * Makes sure the section comment for an element is in the right place.
	 *
	 * An existing section comment that is already in the right place is
	 * corrected if necessary and removed from $existing so that it is kept.
	 * Otherwise, a new one is queued up in $slices.
	 *
	 * @param array<int, true> $existing
	 * @param array<int, list<Token>> $slices
	 */
	private function placeSectionComment(Tokens $tokens, int $start, string $type, array &$existing, array &$slices): void
	{
		$eol = $this->whitespacesConfig->getLineEnding();

		[$insert_at, $boundary, $first] = $this->findInsertionPoint($tokens, $start);

		$indent = $this->getIndentAt($tokens, $first);
		$title = self::TITLES[$type];
		$stars = str_repeat('*', \strlen($title) + 2);

		$comment = implode($eol . $indent, [
			'/' . $stars,
			' * ' . $title,
			' ' . $stars . '/',
		]);

		// There is a blank line before a section comment, unless it comes
		// right after an opening brace. There is always one after it.
		$before = $eol . ($tokens[$boundary]->equals('{') ? '' : $eol) . $indent;
		$after = $eol . $eol . $indent;

		$insert = [];

		if (isset($existing[$insert_at])) {
			// There is a section comment right here already. Keep it, but make
			// sure it is the right one.
			unset($existing[$insert_at]);

			if ($tokens[$insert_at]->getContent() !== $comment) {
				$tokens[$insert_at] = new Token([T_COMMENT, $comment]);
			}

			if (isset($tokens[$insert_at + 1]) && $tokens[$insert_at + 1]->isWhitespace()) {
				$this->setWhitespace($tokens, $insert_at + 1, $after);
			} else {
				$slices[$insert_at + 1] = [new Token([T_WHITESPACE, $after])];
			}
		} else {
			$slices[$insert_at] = [
				new Token([T_COMMENT, $comment]),
				new Token([T_WHITESPACE, $after]),
			];
		}

		if ($tokens[$insert_at - 1]->isWhitespace()) {
			$this->setWhitespace($tokens, $insert_at - 1, $before);
		} else {
			$slices[$insert_at - 1] = [new Token([T_WHITESPACE, $before])];
		}
	}

	/**
	 * Walks back from an element over its modifiers (and its type, for a
	 * property) to the first token of its declaration. Attributes are not
	 * included.
	 *
	 * Along the way, this also works out the element's visibility and whether
	 * it is static. Elements with no explicit visibility, including those
	 * declared with `var`, are public.
	 *
	 * This runs for every element in the file, so it uses plain loops instead
	 * of the much slower Tokens::getPrevMeaningfulToken() and friends.
	 *
	 * @return array{0: int, 1: string, 2: bool} The index of the first token,
	 *                                           'public' or 'internal', and
	 *                                           whether it is static.
	 */
	private function scanModifiers(Tokens $tokens, int $index): array
	{
		$start = $index;
		$visibility = 'public';
		$is_static = false;

		for ($i = $index - 1; $i > 0; $i--) {
			$token = $tokens[$i];
			$id = $token->getId();

			if ($id === T_WHITESPACE || $id === T_COMMENT || $id === T_DOC_COMMENT) {
				continue;
			}

			if ($id === null) {
				$content = $token->getContent();

				if ($content === ';' || $content === '{' || $content === '}') {
					break;
				}
			} elseif ($id === CT::T_ATTRIBUTE_CLOSE) {
				break;
			} elseif ($id === T_PUBLIC) {
				$visibility = 'public';
			} elseif ($id === T_PROTECTED || $id === T_PRIVATE) {
				$visibility = 'internal';
			} elseif ($id === T_STATIC) {
				$is_static = true;
			}

			$start = $i;
		}

		return [$start, $visibility, $is_static];
	}

	/**
	 * A faster stand-in for Tokens::getPrevMeaningfulToken(), for use where
	 * there is known to be a previous token.
	 */
	private function findPreviousMeaningful(Tokens $tokens, int $index): int
	{
		do {
			$index--;
			$id = $tokens[$index]->getId();
		} while ($index > 0 && ($id === T_WHITESPACE || $id === T_COMMENT || $id === T_DOC_COMMENT));

		return $index;
	}

	/**
	 * Finds where the section comment for an element needs to go.
	 *
	 * It goes before the element's attributes, docblock, and any other
	 * comments, but after a comment that trails the previous statement.
	 *
	 * @return array{0: int, 1: int, 2: int} The index to insert at, the index
	 *                                       of the '{', ';' or '}' that comes
	 *                                       before the element, and the index
	 *                                       of the element's first token
	 *                                       (counting attributes).
	 */
	private function findInsertionPoint(Tokens $tokens, int $start): array
	{
		// Rewind to the first attribute in an attribute group.
		$first = $start;

		while ($tokens[$boundary = $this->findPreviousMeaningful($tokens, $first)]->isGivenKind(CT::T_ATTRIBUTE_CLOSE)) {
			$first = $tokens->findBlockStart(Tokens::BLOCK_TYPE_ATTRIBUTE, $boundary);
		}

		$insert_at = $boundary + 1;

		// A comment on the same line as the boundary belongs to whatever
		// came before it, so it has to stay where it is.
		$i = $insert_at;

		if ($i < $first && $tokens[$i]->isWhitespace() && !str_contains($tokens[$i]->getContent(), "\n")) {
			$i++;
		}

		if ($i < $first && $tokens[$i]->isComment()) {
			$insert_at = $i + 1;
		}

		// Step over the line break at the end of the boundary's line.
		if ($insert_at < $first && $tokens[$insert_at]->isWhitespace()) {
			$insert_at++;
		}

		return [$insert_at, $boundary, $first];
	}

	/**
	 * Makes a single pass over the tokens to find the elements of every class
	 * and the section comments that are already there.
	 *
	 * Anonymous classes, and everything inside them, are left out. This does
	 * the job of TokensAnalyzer::getClassyElements() and a search for comments
	 * without calling Tokens::findGivenKind() all in one go, which is cheaper
	 * because it ony traverses the token array once.
	 *
	 * Curly braces and parentheses are counted using only the plain string
	 * tokens. The special tokens that PHP-CS-Fixer makes for things like
	 * string interpolation always come in matching pairs of their own, so
	 * they can be ignored.
	 *
	 * @return array{0: list<list<array{0: int, 1: string}>>, 1: array<int, true>}
	 *         The elements of each class as pairs of an index and a kind
	 *         ('case', 'const', 'method' or 'property'), and the indexes of
	 *         the existing section comments.
	 */
	private function scan(Tokens $tokens): array
	{
		$classes = [];
		$existing = [];

		// The classes whose bodies we are inside of, innermost last.
		$stack = [];
		$top = -1;
		$anonymous_depth = 0;

		// The brace and parenthesis depths at which the elements of the
		// innermost class are found.
		$body_depth = -1;
		$body_paren = -1;

		// Set from when a class keyword is seen until its body is opened.
		$pending = null;

		$depth = 0;
		$paren = 0;

		foreach ($tokens as $i => $token) {
			$id = $token->getId();

			if ($id === null) {
				$content = $token->getContent();

				if ($content === '{') {
					$depth++;

					// Braces within the arguments of an anonymous class are
					// not its body, so the parenthesis depth has to match.
					if ($pending !== null && $pending[0] === $paren) {
						$stack[] = [
							'depth' => $depth,
							'paren' => $paren,
							'anonymous' => $pending[1],
							'elements' => [],
						];

						$top++;
						$body_depth = $depth;
						$body_paren = $paren;

						if ($pending[1]) {
							$anonymous_depth++;
						}

						$pending = null;
					}
				} elseif ($content === '}') {
					if ($depth === $body_depth) {
						$frame = array_pop($stack);
						$top--;

						if ($frame['anonymous']) {
							$anonymous_depth--;
						} else {
							$classes[] = $frame['elements'];
						}

						if ($top >= 0) {
							$body_depth = $stack[$top]['depth'];
							$body_paren = $stack[$top]['paren'];
						} else {
							$body_depth = -1;
							$body_paren = -1;
						}
					}

					$depth--;
				} elseif ($content === '(') {
					$paren++;
				} elseif ($content === ')') {
					$paren--;
				}

				continue;
			}

			if ($id === T_WHITESPACE) {
				continue;
			}

			if ($id === T_COMMENT) {
				$content = $token->getContent();

				if (
					$anonymous_depth === 0
					&& str_starts_with($content, '/*')
					&& preg_match($this->section_comment_regex, $content) === 1
				) {
					$existing[$i] = true;
				}

				continue;
			}

			if ($id === T_CLASS || $id === T_INTERFACE || $id === T_TRAIT || $id === T_ENUM) {
				$pending = [$paren, $id === T_CLASS && $this->isAnonymousClass($tokens, $i)];

				continue;
			}

			// Elements are only found directly inside a class body. This keeps
			// out variables in method bodies and, because of the parenthesis
			// depth, parameters, including promoted ones.
			if ($depth !== $body_depth || $paren !== $body_paren) {
				continue;
			}

			if ($id === T_VARIABLE) {
				$stack[$top]['elements'][] = [$i, 'property'];
			} elseif ($id === T_FUNCTION) {
				$stack[$top]['elements'][] = [$i, 'method'];
			} elseif ($id === T_CONST) {
				$stack[$top]['elements'][] = [$i, 'const'];
			} elseif ($id === T_CASE) {
				$stack[$top]['elements'][] = [$i, 'case'];
			}
		}

		return [$classes, $existing];
	}

	/**
	 * Tells whether a T_CLASS token starts an anonymous class, which looks
	 * like `new class`, `new readonly class` or `new #[Attribute] class`.
	 */
	private function isAnonymousClass(Tokens $tokens, int $index): bool
	{
		$prev = $this->findPreviousMeaningful($tokens, $index);

		if ($tokens[$prev]->isGivenKind(T_READONLY)) {
			$prev = $this->findPreviousMeaningful($tokens, $prev);
		}

		while ($tokens[$prev]->isGivenKind(CT::T_ATTRIBUTE_CLOSE)) {
			$prev = $this->findPreviousMeaningful($tokens, $tokens->findBlockStart(Tokens::BLOCK_TYPE_ATTRIBUTE, $prev));
		}

		return $tokens[$prev]->isGivenKind(T_NEW);
	}

	/**
	 * Gets the indentation of the line that a token is on, as long as that
	 * token is the first thing on its line.
	 */
	private function getIndentAt(Tokens $tokens, int $index): string
	{
		$prev = $tokens[$index - 1];

		if ($prev->isWhitespace()) {
			$position = strrpos($prev->getContent(), "\n");

			if ($position !== false) {
				return substr($prev->getContent(), $position + 1);
			}
		}

		return $this->whitespacesConfig->getIndent();
	}

	/**
	 * Replaces the content of a whitespace token, but only if it differs, so
	 * that untouched code is not reported as changed.
	 */
	private function setWhitespace(Tokens $tokens, int $index, string $content): void
	{
		if ($tokens[$index]->getContent() !== $content) {
			$tokens[$index] = new Token([T_WHITESPACE, $content]);
		}
	}
}
