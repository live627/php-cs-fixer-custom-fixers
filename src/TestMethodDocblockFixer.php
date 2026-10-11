<?php

declare(strict_types=1);

namespace Live627\PhpCsFixer\CustomFixers;

use PhpCsFixer\AbstractFixer;
use PhpCsFixer\Fixer\WhitespacesAwareFixerInterface;
use PhpCsFixer\FixerDefinition\CodeSample;
use PhpCsFixer\FixerDefinition\FixerDefinition;
use PhpCsFixer\FixerDefinition\FixerDefinitionInterface;
use PhpCsFixer\Tokenizer\Analyzer\WhitespacesAnalyzer;
use PhpCsFixer\Tokenizer\CT;
use PhpCsFixer\Tokenizer\Token;
use PhpCsFixer\Tokenizer\Tokens;

/**
 * Adds missing docblocks and docblock elements to public test methods and
 * enforces their indentation.
 *
 * Within classes whose names end in `Test`, every public method whose name
 * starts with `test` gets a docblock if it does not already have one. The
 * generated docblock has a summary derived from the method name and a stub
 * `Expected:` section to be filled in:
 *
 *     public function testFooBar(): void
 *
 * becomes:
 *
 *     /**
 *      * Foo bar.
 *      *
 *      * Expected: TODO describe the call and the assertion result.
 *      *\/
 *     public function testFooBar(): void
 *
 * Existing docblocks that lack a summary or an `Expected:` section get the
 * missing element added. The `Expected:` section goes after the description
 * and before any tags such as `@dataProvider`.
 *
 * Docblocks (new or existing) on these methods are aligned with the method
 * declaration, one indentation level deeper than the class.
 *
 * @author John Rayes <live627@gmail.com>
 *
 * @no-named-arguments Parameter names are not covered by the backward compatibility promise.
 */
final class TestMethodDocblockFixer extends AbstractFixer implements WhitespacesAwareFixerInterface
{
	/*****************
	 * Class constants
	 *****************/

	private const TEST_CLASS_SUFFIX = 'Test';
	private const TEST_METHOD_PREFIX = 'test';
	private const EXPECTED_STUB = 'Expected: TODO describe the call and the assertion result.';

	/****************
	 * Public methods
	 ****************/

	public function getName(): string
	{
		return 'Live627/' . parent::getName();
	}

	public function getDefinition(): FixerDefinitionInterface
	{
		return new FixerDefinition(
			'Adds missing docblocks, summaries and `Expected:` sections to public test methods and aligns them with the method declaration.',
			[
				new CodeSample(
					<<<'PHP'
						<?php

						namespace Foo;

						class FooTest extends TestCase
						{
							public function testSomethingWorks(): void
							{
								$this->assertTrue(true);
							}

							/**
								* Already documented.
							  */
							#[DataProvider('provideValues')]
							public function testValues(int $value): void
							{
								$this->assertGreaterThan(0, $value);
							}

							private function helper(): void
							{
							}
						}

						PHP,
				),
				new CodeSample(
					<<<'PHP'
						<?php

						class FooTest extends TestCase
						{
							/**
							 * Does something.
							 *
							 * @dataProvider provideValues
							 */
							public function testSomething(int $value): void
							{
							}

							/**
							 * @dataProvider provideOther
							 */
							public function testOther(int $value): void
							{
							}
						}

						PHP,
				),
			],
		);
	}

	/**
	 * {@inheritdoc}
	 *
	 * Must run after PhpUnitTestAnnotationFixer.
	 */
	public function getPriority(): int
	{
		return 0;
	}

	public function isCandidate(Tokens $tokens): bool
	{
		return $tokens->isAllTokenKindsFound([\T_CLASS, \T_FUNCTION])
			&& $tokens->isMonolithicPhp();
	}

	/******************
	 * Internal methods
	 ******************/

	protected function applyFix(\SplFileInfo $file, Tokens $tokens): void
	{
		$indent_unit = $this->whitespacesConfig->getIndent();
		$line_ending = $this->whitespacesConfig->getLineEnding();
		$slices = [];

		// The test class we are currently inside of, if any.
		$class_index = null;
		$class_end = null;

		foreach ($tokens as $index => $token) {
			if ($token->isGivenKind(\T_CLASS)) {
				$name_index = $tokens->getNextMeaningfulToken($index);

				// Skip `Foo::class` and anonymous classes.
				if (
					$tokens[$tokens->getPrevMeaningfulToken($index)]->isGivenKind(\T_DOUBLE_COLON)
					|| !$tokens[$name_index]->isGivenKind(\T_STRING)
				) {
					continue;
				}

				if (str_ends_with($tokens[$name_index]->getContent(), self::TEST_CLASS_SUFFIX)) {
					$class_index = $index;
					$class_end = $tokens->findBlockEnd(
						Tokens::BLOCK_TYPE_CURLY_BRACE,
						$tokens->getNextTokenOfKind($index, ['{']),
					);
				} else {
					$class_index = null;
					$class_end = null;
				}

				continue;
			}

			if ($class_index === null || $index > $class_end || !$token->isGivenKind(\T_FUNCTION)) {
				continue;
			}

			$name_index = $tokens->getNextMeaningfulToken($index);

			// Skip the reference marker in `function &testFoo()`.
			if ($tokens[$name_index]->equals('&')) {
				$name_index = $tokens->getNextMeaningfulToken($name_index);
			}

			// Closures have no name.
			if (!$tokens[$name_index]->isGivenKind(\T_STRING)) {
				continue;
			}

			$name = $tokens[$name_index]->getContent();

			if (!str_starts_with($name, self::TEST_METHOD_PREFIX)) {
				continue;
			}

			[$start, $doc_index, $is_public] = $this->findDeclarationStart($tokens, $index);

			if (!$is_public) {
				continue;
			}

			$indent = WhitespacesAnalyzer::detectIndent($tokens, $class_index) . $indent_unit;

			// Existing docblock: align it and add any missing elements.
			if ($doc_index !== null) {
				$content = $this->addMissingElements(
					preg_replace(
						[
							// Blank lines lose all their whitespace.
							'/\R[ \t]*(?=\R)/',
							// Every other line is aligned with the docblock's stars.
							'/\R[ \t]*(?=\S)/',
						],
						[
							$line_ending,
							$line_ending . $indent . ' ',
						],
						$tokens[$doc_index]->getContent(),
					),
					$name,
					$indent,
					$line_ending,
				);

				$tokens[$doc_index] = new Token([\T_DOC_COMMENT, $content]);

				$this->setIndentBefore($tokens, $doc_index, $indent);
				$this->setIndentBefore($tokens, $tokens->getNextNonWhitespace($doc_index), $indent);

				continue;
			}

			$this->setIndentBefore($tokens, $start, $indent);

			$slices[$start] = [
				new Token([\T_DOC_COMMENT, $this->buildDocBlock($name, $indent, $line_ending)]),
				new Token([\T_WHITESPACE, $line_ending . $indent]),
			];
		}

		if ($slices !== []) {
			$tokens->insertSlices($slices);
		}
	}

	/**
	 * Walks back from a `function` keyword over modifiers and attribute groups.
	 *
	 * @return array{int, int|null, bool} The index of the first token of the method
	 *                                    declaration, the index of its docblock (if any),
	 *                                    and whether the method is public (no explicit
	 *                                    visibility counts as public).
	 */
	private function findDeclarationStart(Tokens $tokens, int $function_index): array
	{
		$start = $function_index;
		$is_public = true;

		while (true) {
			$prev = $tokens->getPrevNonWhitespace($start);

			if ($prev === null) {
				break;
			}

			$token = $tokens[$prev];

			if ($token->isGivenKind(\T_DOC_COMMENT)) {
				return [$start, $prev, $is_public];
			}

			if ($token->isGivenKind([
				\T_PUBLIC,
				\T_PROTECTED,
				\T_PRIVATE,
				\T_STATIC,
				\T_FINAL,
				\T_ABSTRACT,
				\T_READONLY,
			])) {
				if ($token->isGivenKind([\T_PROTECTED, \T_PRIVATE])) {
					$is_public = false;
				}

				$start = $prev;

				continue;
			}

			if ($token->isGivenKind(CT::T_ATTRIBUTE_CLOSE)) {
				$start = $tokens->findBlockStart(Tokens::BLOCK_TYPE_ATTRIBUTE, $prev);

				continue;
			}

			// Any other token, including regular comments such as section
			// comments, ends the declaration.
			break;
		}

		return [$start, null, $is_public];
	}

	/**
	 * Replaces the indentation after the last newline of the whitespace token
	 * that precedes the given index.
	 */
	private function setIndentBefore(Tokens $tokens, int $index, string $indent): void
	{
		$prev = $index - 1;

		if (!isset($tokens[$prev]) || !$tokens[$prev]->isWhitespace()) {
			return;
		}

		$content = $tokens[$prev]->getContent();
		$newline_pos = strrpos($content, "\n");

		if ($newline_pos === false) {
			return;
		}

		$tokens[$prev] = new Token([\T_WHITESPACE, substr($content, 0, $newline_pos + 1) . $indent]);
	}

	/**
	 * Adds a summary and an `Expected:` section to an existing docblock if
	 * they are missing. The `Expected:` section goes after the description
	 * and before the first tag.
	 */
	private function addMissingElements(string $content, string $method_name, string $indent, string $line_ending): string
	{
		if (str_contains($content, 'Expected:')) {
			return $content;
		}

		$lines = preg_split('/\R/', $content);

		// Expand single-line docblocks such as "/** Foo. */".
		if (\count($lines) === 1) {
			if (!preg_match('/^\/\*\*(.*?)\*\/$/s', $content, $match)) {
				return $content;
			}

			$lines = ['/**'];
			$inner = trim($match[1]);

			if ($inner !== '') {
				$lines[] = $indent . ' * ' . $inner;
			}

			$lines[] = $indent . ' */';
		}

		// Leave anything with text on the opening or closing line alone.
		if (trim($lines[0]) !== '/**' || trim($lines[\count($lines) - 1]) !== '*/') {
			return $content;
		}

		$body = \array_slice($lines, 1, -1);
		$tag_index = null;

		foreach ($body as $i => $line) {
			if (preg_match('/^\s*\*\s*@/', $line)) {
				$tag_index = $i;

				break;
			}
		}

		$description = $tag_index === null ? $body : \array_slice($body, 0, $tag_index);
		$tags = $tag_index === null ? [] : \array_slice($body, $tag_index);

		while ($description !== [] && preg_match('/^\s*\*?\s*$/', $description[\count($description) - 1])) {
			array_pop($description);
		}

		if ($description === []) {
			$description[] = $indent . ' * ' . $this->buildSummary($method_name);
		}

		$description[] = $indent . ' *';
		$description[] = $indent . ' * ' . self::EXPECTED_STUB;

		if ($tags !== []) {
			$description[] = $indent . ' *';
		}

		return implode($line_ending, [
			$lines[0],
			...$description,
			...$tags,
			$lines[\count($lines) - 1],
		]);
	}

	private function buildDocBlock(string $method_name, string $indent, string $line_ending): string
	{
		return implode($line_ending, [
			'/**',
			$indent . ' * ' . $this->buildSummary($method_name),
			$indent . ' *',
			$indent . ' * ' . self::EXPECTED_STUB,
			$indent . ' */',
		]);
	}

	/**
	 * Turns "testFooBar_baz" into "Foo bar baz.".
	 */
	private function buildSummary(string $method_name): string
	{
		$words = preg_split(
			'/(?<=[a-z0-9])(?=[A-Z])|_+/',
			substr($method_name, \strlen(self::TEST_METHOD_PREFIX)),
			-1,
			PREG_SPLIT_NO_EMPTY,
		);

		if ($words === []) {
			return 'TODO summary.';
		}

		return ucfirst(strtolower(implode(' ', $words))) . '.';
	}
}
