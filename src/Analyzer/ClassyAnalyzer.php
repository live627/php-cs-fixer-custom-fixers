<?php

declare(strict_types=1);

namespace Live627\PhpCsFixer\CustomFixers\Analyzer;

use PhpCsFixer\Tokenizer\CT;
use PhpCsFixer\Tokenizer\Tokens;

/**
 * Determines whether a T_STRING token is being used as a class-like reference.
 *
 * This class is a fork of PHP-CS-Fixer's internal ClassyAnalyzer.
 *
 * It exists to allow local performance optimizations without modifying vendor
 * code. The primary optimization replaces TypeAnalysis object construction
 * during reserved-type checks with a static hash map lookup.
 *
 * Benchmarking showed this analyzer to be on a hot path for
 * GlobalNativeNamespaceImportFixer, making object-allocation overhead
 * measurable when processing large files.
 *
 * The implementation should remain behaviorally compatible with the upstream
 * analyzer and should be reviewed against upstream changes when updating
 * PHP-CS-Fixer.
 *
 * @internal
 *
 * @no-named-arguments Parameter names are not covered by the backward compatibility promise.
 */
final class ClassyAnalyzer
{
	/*****************
	 * Class constants
	 *****************/

	private const RESERVED_TYPES = [
		'array' => true,
		'bool' => true,
		'callable' => true,
		'false' => true,
		'float' => true,
		'int' => true,
		'iterable' => true,
		'list' => true,
		'mixed' => true,
		'never' => true,
		'null' => true,
		'object' => true,
		'parent' => true,
		'resource' => true,
		'self' => true,
		'static' => true,
		'string' => true,
		'true' => true,
		'void' => true,
	];

	/****************
	 * Public methods
	 ****************/

	public function isClassyInvocation(Tokens $tokens, int $index): bool
	{
		$token = $tokens[$index];

		if (!$token->isGivenKind(\T_STRING)) {
			throw new \LogicException(\sprintf('No T_STRING at given index %d, got "%s".', $index, $tokens[$index]->getName()));
		}

		$content = $token->getContent();

		if (isset(self::RESERVED_TYPES[strtolower($content)])) {
			return false;
		}

		$next = $tokens->getNextMeaningfulToken($index);
		$next_token = $tokens[$next];

		if ($next_token->isGivenKind(\T_NS_SEPARATOR)) {
			return false;
		}

		if ($next_token->isGivenKind([\T_DOUBLE_COLON, \T_ELLIPSIS, CT::T_TYPE_ALTERNATION, CT::T_TYPE_INTERSECTION, \T_VARIABLE])) {
			return true;
		}

		$prev = $tokens->getPrevMeaningfulToken($index);

		while ($tokens[$prev]->isGivenKind([CT::T_NAMESPACE_OPERATOR, \T_NS_SEPARATOR, \T_STRING])) {
			$prev = $tokens->getPrevMeaningfulToken($prev);
		}

		$prev_token = $tokens[$prev];

		if ($prev_token->isGivenKind([\T_EXTENDS, \T_INSTANCEOF, \T_INSTEADOF, \T_IMPLEMENTS, \T_NEW, CT::T_NULLABLE_TYPE, CT::T_TYPE_ALTERNATION, CT::T_TYPE_INTERSECTION, CT::T_TYPE_COLON, CT::T_USE_TRAIT])) {
			return true;
		}

		if (\PHP_VERSION_ID >= 8_00_00 && $next_token->equals(')') && $prev_token->equals('(') && $tokens[$tokens->getPrevMeaningfulToken($prev)]->isGivenKind(\T_CATCH)) {
			return true;
		}

		if (\PhpCsFixer\Tokenizer\Analyzer\AttributeAnalyzer::isAttribute($tokens, $index)) {
			return true;
		}

		// `Foo & $bar` could be:
		//   - function reference parameter: function baz(Foo & $bar) {}
		//   - bit operator: $x = Foo & $bar;
		if ($next_token->equals('&') && $tokens[$tokens->getNextMeaningfulToken($next)]->isGivenKind(\T_VARIABLE)) {
			$check_index = $tokens->getPrevTokenOfKind($prev + 1, [';', '{', '}', [\T_FUNCTION], [\T_OPEN_TAG], [\T_OPEN_TAG_WITH_ECHO]]);

			return $tokens[$check_index]->isGivenKind(\T_FUNCTION);
		}

		if (!$prev_token->equals(',')) {
			return false;
		}

		do {
			$prev = $tokens->getPrevMeaningfulToken($prev);
			$prev_token = $tokens[$prev];
		} while (
			$prev_token->equals(',')
			|| $prev_token->isGivenKind(T_NS_SEPARATOR)
			|| $prev_token->isGivenKind(T_STRING)
			|| $prev_token->isGivenKind(CT::T_NAMESPACE_OPERATOR)
		);

		return $tokens[$prev]->isGivenKind([\T_IMPLEMENTS, CT::T_USE_TRAIT]);
	}
}
