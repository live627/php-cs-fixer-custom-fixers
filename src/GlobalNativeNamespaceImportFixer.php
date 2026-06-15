<?php

declare(strict_types=1);

namespace Live627\PhpCsFixer\CustomFixers;

use PhpCsFixer\AbstractFixer;
use PhpCsFixer\DocBlock\Annotation;
use PhpCsFixer\DocBlock\DocBlock;
use PhpCsFixer\Fixer\ConfigurableFixerInterface;
use PhpCsFixer\Fixer\ConfigurableFixerTrait;
use PhpCsFixer\Fixer\WhitespacesAwareFixerInterface;
use PhpCsFixer\FixerConfiguration\FixerConfigurationResolver;
use PhpCsFixer\FixerConfiguration\FixerConfigurationResolverInterface;
use PhpCsFixer\FixerConfiguration\FixerOptionBuilder;
use PhpCsFixer\FixerDefinition\CodeSample;
use PhpCsFixer\FixerDefinition\FixerDefinition;
use PhpCsFixer\FixerDefinition\FixerDefinitionInterface;
use PhpCsFixer\Preg;
use PhpCsFixer\Tokenizer\Analyzer\Analysis\NamespaceUseAnalysis;
use Live627\PhpCsFixer\CustomFixers\Analyzer\ClassyAnalyzer;
use PhpCsFixer\Tokenizer\Analyzer\FunctionsAnalyzer;
use PhpCsFixer\Tokenizer\Analyzer\NamespaceUsesAnalyzer;
use PhpCsFixer\Tokenizer\CT;
use PhpCsFixer\Tokenizer\Processor\ImportProcessor;
use PhpCsFixer\Tokenizer\Token;
use PhpCsFixer\Tokenizer\Tokens;
use PhpCsFixer\Tokenizer\TokensAnalyzer;

/**
 * Replaces imports of PHP internal classes, interfaces, and traits with
 * fully-qualified references.
 *
 * This fixer removes non-aliased `use` statements that import symbols from the
 * global PHP namespace and updates all corresponding references, including
 * PHPDoc annotations, to use their fully-qualified names.
 *
 * Example:
 *
 *     use DateTime;
 *
 *     new DateTime();
 *
 * becomes:
 *
 *     new \DateTime();
 *
 * This reduces import noise while making built-in PHP symbols explicit.
 *
 * @no-named-arguments Parameter names are not covered by the backward compatibility promise.
 */
final class GlobalNativeNamespaceImportFixer extends AbstractFixer
{
	public function __construct()
	{
		parent::__construct();

		self::$internalClasses ??= $this->buildInternalClassMap();
	}

	public function getDefinition(): FixerDefinitionInterface
	{
		return new FixerDefinition(
			'Fully qualifies references to PHP internal classes, interfaces, and traits and removes redundant import statements.',
			[
				new CodeSample(
					<<<'PHP'
						<?php

						namespace Foo;

						use DateTime;
						use DateTimeImmutable;
						use App\Bar;

						$d = new DateTime();
						$i = new DateTimeImmutable();
						$b = new Bar();

						PHP,
				),
				new CodeSample(
					<<<'PHP'
						<?php

						namespace App;

						use App\Model\User;
						use App\Service\UserManager;
						use Countable;
						use DateTime;
						use DateTimeImmutable;
						use Iterator;
						use IteratorAggregate;
						use RuntimeException;

						/**
						 * This is an exceptional test classs.
						 */
						class Test extends RuntimeException implements IteratorAggregate
						{
							/**
							 * @var DateTime
							 */
							private DateTime $date;

							/**
							 * @var User
							 */
							private User $user;

							private string $class = DateTime::class;

							/**
							 * @param DateTime|DateTimeImmutable $date
							 *
							 * @return DateTime
							 */
							public function run(DateTime|DateTimeImmutable $date): DateTime
							{
								if ($date instanceof DateTimeImmutable) {
									throw new RuntimeException();
								}

								DateTime::createFromFormat('Y-m-d', '2026-01-01');

								new DateTime();
								new DateTimeImmutable();

								$manager = new UserManager();

								return new DateTime();
							}

							/**
							 * @param Iterator&Countable $value
							 */
							public function intersect(Iterator&Countable $value): void
							{
							}

							/**
							 * @return Iterator<int, User>
							 */
							public function getIterator(): Iterator
							{
								yield $this->user;
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
	 * Must run before NoUnusedImportsFixer, OrderedImportsFixer, StatementIndentationFixer.
	 * Must run after NativeConstantInvocationFixer, NativeFunctionInvocationFixer, StringableForToStringFixer.
	 */
	public function getPriority(): int
	{
		return 0;
	}

	public function isCandidate(Tokens $tokens): bool
	{
		return $tokens->isTokenKindFound(\T_USE)
			&& $tokens->countTokenKind(\T_NAMESPACE) > 0
			&& $tokens->isMonolithicPhp();
	}

	protected function applyFix(\SplFileInfo $file, Tokens $tokens): void
	{
		$useDeclarations = (new NamespaceUsesAnalyzer())->getDeclarationsFromTokens($tokens);
		$slices = [];
		$importsToRemove = [];

		$global = [];

		foreach ($useDeclarations as $declaration) {
			if (!$declaration->isClass() || $declaration->isAliased()) {
				continue;
			}

			$full_name = ltrim($declaration->getFullName(), '\\');

			if (!isset(self::$internalClasses[strtolower($full_name)])) {
				continue;
			}

			$qualified_name = '\\' . $full_name;

			$importsToRemove[] = [
				$declaration->getStartIndex(),
				$declaration->getEndIndex(),
			];

			$global[$full_name] = $qualified_name;
			$global[strtolower($full_name)] = $qualified_name;
		}

		if ([] === $global) {
			return;
		}

		$analyzer = new ClassyAnalyzer();
		$start = $importsToRemove === [] ? 0 : array_last($importsToRemove)[1];

		for ($index = $tokens->count() - 1; $index >= $start; --$index) {
			$token = $tokens[$index];
			$token_content = $token->getContent();

			if ($token->isGivenKind(T_DOC_COMMENT)) {
				$doc = preg_replace_callback(
					'/(?<![\w\\\\])[\w\\\\]+(?![\w\\\\:])/',
					fn($var) => $global[$var[0]] ?? $var[0],
					$token_content,
				);

				$tokens[$index] = new Token([T_DOC_COMMENT, $doc]);

				continue;
			}

			if (!$token->isGivenKind(\T_STRING) || !isset($global[$token_content])) {
				continue;
			}

			if ($tokens[$tokens->getPrevMeaningfulToken($index)]->isGivenKind(\T_NS_SEPARATOR)) {
				continue;
			}

			if (!$analyzer->isClassyInvocation($tokens, $index)) {
				continue;
			}

			$slices[$index] = new Token([\T_NS_SEPARATOR, '\\']);
		}

		foreach ($importsToRemove as [$start, $end]) {
			$tokens->clearRange($start, $end + 1);
		}

		if ($slices !== []) {
			$tokens->insertSlices($slices);
		}
	}

	/**
	 * @var array<string, true>|null
	 */
	private static ?array $internalClasses = null;

	/**
	 * @return array<string, true>
	 */
	private function buildInternalClassMap(): array
	{
		$result = [];

		foreach (
			array_merge(
				get_declared_classes(),
				get_declared_interfaces(),
				get_declared_traits()
			) as $name
		) {
			$reflection = new \ReflectionClass($name);

			if ($reflection->isInternal()) {
				$result[strtolower($name)] = true;
			}
		}

		return $result;
	}
}