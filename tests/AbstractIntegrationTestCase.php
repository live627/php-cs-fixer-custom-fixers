<?php

declare(strict_types=1);

/*
 * This file is part of PHP CS Fixer.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *     Dariusz Rumiński <dariusz.ruminski@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

use PhpCsFixer\Cache\NullCacheManager;
use PhpCsFixer\Differ\UnifiedDiffer;
use PhpCsFixer\Error\Error;
use PhpCsFixer\Error\ErrorsManager;
use PhpCsFixer\FileRemoval;
use PhpCsFixer\Fixer\FixerInterface;
use PhpCsFixer\FixerFactory;
use PhpCsFixer\Linter\CachingLinter;
use PhpCsFixer\Linter\Linter;
use PhpCsFixer\Linter\LinterInterface;
use PhpCsFixer\Linter\ProcessLinter;
use PhpCsFixer\PhpunitConstraintIsIdenticalString\Constraint\IsIdenticalString;
use PhpCsFixer\Runner\Runner;
use PhpCsFixer\Tests\Test\IntegrationCase;
use PhpCsFixer\Tests\Test\IntegrationCaseFactory;
use PhpCsFixer\Tests\Test\IntegrationCaseFactoryInterface;
use PhpCsFixer\Tests\TestCase;
use PhpCsFixer\Tokenizer\Tokens;
use PhpCsFixer\WhitespacesFixerConfig;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use Symfony\Component\Filesystem\Exception\IOException;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;

/**
 * Integration test base class.
 *
 * This test searches for '.test' fixture files in the given directory.
 * Each fixture file will be parsed and tested against the expected result.
 *
 * Fixture files have the following format:
 *
 * --TEST--
 * Example test description.
 * --RULESET--
 * {"@PSR2": true, "strict": true}
 * --CONFIG--
 * {"indent": "    ", "lineEnding": "\n"}
 * --SETTINGS--
 * {"key": "value"} # optional extension point for custom IntegrationTestCase class
 * --REQUIREMENTS--
 * {"php": 70400, "php<": 80000}
 * --EXPECT--
 * Expected code after fixing
 * --INPUT--
 * Code to fix
 *
 **************
 * IMPORTANT! *
 **************
 *
 * Some sections (like `--CONFIG--`) may be omitted. The required sections are:
 *   - `--TEST--`
 *   - `--RULESET--`
 *   - `--EXPECT--` (works as input too if `--INPUT--` is not provided, that means no changes are expected)
 *
 * The `--REQUIREMENTS--` section can define additional constraints for running (or not) the test.
 * You can use these fields to fine-tune run conditions for test cases:
 *   - `php` represents minimum PHP version test should be run on. Defaults to current running PHP version (no effect).
 *   - `php<` represents maximum PHP version test should be run on. Defaults to PHP's maximum integer value (no effect).
 *   - `os` represents operating system(s) test should be run on. Supported operating systems: Linux, Darwin and Windows.
 *     By default test is run on all supported operating systems.
 *
 * @internal
 *
 * @no-named-arguments Parameter names are not covered by the backward compatibility promise.
 */
abstract class AbstractIntegrationTestCase extends TestCase
{
	/*********************
	 * Internal properties
	 *********************/

	protected ?LinterInterface $linter = null;

	/****************************
	 * Internal static properties
	 ****************************/

	private static ?FileRemoval $file_removal = null;

	/****************
	 * Public methods
	 ****************/

	/**
	 * @dataProvider provideIntegrationCases
	 *
	 * @see doTest()
	 *
	 * @large
	 *
	 * @group legacy
	 */
	#[DataProvider('provideIntegrationCases')]
	#[Group('legacy')]
	public function testIntegration(IntegrationCase $case): void
	{
		foreach ($case->getSettings()['deprecations'] as $deprecation) {
			$this->expectDeprecation($deprecation);
		}

		$this->doTest($case);

		// run the test again with the `expected` part, this should always stay the same
		$this->doTest(
			new IntegrationCase(
				$case->getFileName(),
				$case->getTitle() . ' "--EXPECT-- part run"',
				$case->getSettings(),
				$case->getRequirements(),
				$case->getConfig(),
				$case->getRuleset(),
				$case->getExpectedCode(),
				null,
			),
		);
	}

	/***********************
	 * Public static methods
	 ***********************/

	public static function setUpBeforeClass(): void
	{
		parent::setUpBeforeClass();

		$tmp_file = static::getTempFile();
		self::$file_removal = new FileRemoval();
		self::$file_removal->observe($tmp_file);

		if (!is_file($tmp_file)) {
			$dir = dirname($tmp_file);

			if (!is_dir($dir)) {
				$fs = new Filesystem();
				$fs->mkdir($dir, 0766);
			}
		}
	}

	public static function tearDownAfterClass(): void
	{
		parent::tearDownAfterClass();

		$tmp_file = static::getTempFile();

		self::$file_removal->delete($tmp_file);
		self::$file_removal = null;
	}

	/**
	 * Creates test data by parsing '.test' files.
	 *
	 * @return iterable<string, array{IntegrationCase}>
	 */
	public static function provideIntegrationCases(): iterable
	{
		$dir = static::getFixturesDir();
		$fixtures_dir = realpath($dir);
		assert(is_string($fixtures_dir));

		if (!is_dir($fixtures_dir)) {
			throw new \UnexpectedValueException(sprintf('Given fixture dir "%s" is not a directory.', $fixtures_dir));
		}

		$factory = static::createIntegrationCaseFactory();

		foreach (Finder::create()->files()->in($fixtures_dir) as $file) {
			if ($file->getExtension() !== 'test') {
				continue;
			}

			$relative_path = substr($file->getPathname(), strlen((string) realpath(__DIR__ . '/../../')) + 1);

			yield $relative_path => [$factory->create($file)];
		}
	}

	/******************
	 * Internal methods
	 ******************/

	protected function setUp(): void
	{
		parent::setUp();

		$this->linter = $this->getLinter();
	}

	protected function tearDown(): void
	{
		parent::tearDown();

		$this->linter = null;
	}

	/**
	 * Applies the given fixers on the input and checks the result.
	 *
	 * It will write the input to a temp file. The file will be fixed by a Fixer instance
	 * configured with the given fixers. The result is compared with the expected output.
	 * It checks if no errors were reported during the fixing.
	 */
	protected function doTest(IntegrationCase $case): void
	{
		$php_lower_limit = $case->getRequirement('php');

		if ($php_lower_limit > \PHP_VERSION_ID) {
			self::markTestSkipped(sprintf('PHP %d (or later) is required for "%s", current "%d".', $php_lower_limit, $case->getFileName(), \PHP_VERSION_ID));
		}

		$php_upper_limit = $case->getRequirement('php<');

		if ($php_upper_limit <= \PHP_VERSION_ID) {
			self::markTestSkipped(sprintf('PHP lower than %d is required for "%s", current "%d".', $php_upper_limit, $case->getFileName(), \PHP_VERSION_ID));
		}

		if (!in_array(\PHP_OS_FAMILY, $case->getRequirement('os'), true)) {
			self::markTestSkipped(
				sprintf(
					'Unsupported OS (%s) for "%s", allowed are: %s.',
					\PHP_OS,
					$case->getFileName(),
					implode(', ', $case->getRequirement('os')),
				),
			);
		}

		$input = $case->getInputCode();
		$expected = $case->getExpectedCode();

		$input = $case->hasInputCode() ? $input : $expected;

		$tmp_file = static::getTempFile();

		if (@file_put_contents($tmp_file, $input) === false) {
			throw new IOException(sprintf('Failed to write to tmp. file "%s".', $tmp_file));
		}

		$errors_manager = new ErrorsManager();
		$fixers = self::createFixers($case);
		$runner = new Runner(
			new \ArrayIterator([new \SplFileInfo($tmp_file)]),
			$fixers,
			new UnifiedDiffer(),
			null,
			$errors_manager,
			$this->linter,
			false,
			new NullCacheManager(),
		);

		Tokens::clearCache();
		$result = $runner->fix();
		$changed = array_pop($result);

		if (!$errors_manager->isEmpty()) {
			$errors = $errors_manager->getExceptionErrors();
			self::assertEmpty($errors, sprintf('Errors reported during fixing of file "%s": %s', $case->getFileName(), $this->implodeErrors($errors)));

			$errors = $errors_manager->getInvalidErrors();
			self::assertEmpty($errors, sprintf('Errors reported during linting before fixing file "%s": %s.', $case->getFileName(), $this->implodeErrors($errors)));

			$errors = $errors_manager->getLintErrors();
			self::assertEmpty($errors, sprintf('Errors reported during linting after fixing file "%s": %s.', $case->getFileName(), $this->implodeErrors($errors)));
		}

		if (!$case->hasInputCode()) {
			self::assertEmpty(
				$changed,
				sprintf(
					"Expected no changes made to test \"%s\" in \"%s\".\nFixers applied:\n%s.\nDiff.:\n%s.",
					$case->getTitle(),
					$case->getFileName(),
					$changed === null ? '[None]' : implode(',', $changed['appliedFixers']),
					$changed === null ? '[None]' : $changed['diff'],
				),
			);

			return;
		}

		self::assertNotEmpty($changed, sprintf('Expected changes made to test "%s" in "%s".', $case->getTitle(), $case->getFileName()));

		$fixed_input_code = file_get_contents($tmp_file);
		self::assertIsString($fixed_input_code);

		self::assertThat(
			$fixed_input_code,
			new IsIdenticalString($expected),
			sprintf(
				"Expected changes do not match result for \"%s\" in \"%s\".\nFixers applied:\n%s.",
				$case->getTitle(),
				$case->getFileName(),
				implode(',', $changed['appliedFixers']),
			),
		);

		if (count($fixers) > 1) {
			$tmp_file = static::getTempFile();

			if (@file_put_contents($tmp_file, $input) === false) {
				throw new IOException(sprintf('Failed to write to tmp. file "%s".', $tmp_file));
			}

			$runner = new Runner(
				new \ArrayIterator([new \SplFileInfo($tmp_file)]),
				array_reverse($fixers),
				new UnifiedDiffer(),
				null,
				$errors_manager,
				$this->linter,
				false,
				new NullCacheManager(),
			);

			Tokens::clearCache();
			$runner->fix();

			$fixed_input_code_with_reversed_fixers = file_get_contents($tmp_file);
			self::assertIsString($fixed_input_code_with_reversed_fixers);

			static::assertRevertedOrderFixing($case, $fixed_input_code, $fixed_input_code_with_reversed_fixers);
		}
	}

	/**
	 * @param list<Error> $errors
	 */
	private function implodeErrors(array $errors): string
	{
		$error_str = '';

		foreach ($errors as $error) {
			$source = $error->getSource();
			$error_str .= sprintf(
				"\n\n[%s] %s\n\nDIFF:\n\n%s\n\nAPPLIED FIXERS:\n\n%s\n\nSTACKTRACE:\n\n%s\n",
				$error->getFilePath(),
				$source === null ? '' : $source->getMessage(),
				$error->getDiff(),
				implode(', ', $error->getAppliedFixers()),
				$source->getTraceAsString(),
			);
		}

		return $error_str;
	}

	private function getLinter(): LinterInterface
	{
		static $linter = null;

		if ($linter === null) {
			$linter = new CachingLinter(
				filter_var(getenv('PHP_CS_FIXER_FAST_LINT_TEST_CASES'), \FILTER_VALIDATE_BOOLEAN)
					? new Linter()
					: new ProcessLinter(),
			);
		}

		return $linter;
	}

	/*************************
	 * Internal static methods
	 *************************/

	protected static function createIntegrationCaseFactory(): IntegrationCaseFactoryInterface
	{
		return new IntegrationCaseFactory();
	}

	/**
	 * Returns the full path to directory which contains the tests.
	 */
	protected static function getFixturesDir(): string
	{
		throw new \BadMethodCallException('Method "getFixturesDir" must be overridden by the extending class.');
	}

	/**
	 * Returns the full path to the temporary file where the test will write to.
	 */
	protected static function getTempFile(): string
	{
		throw new \BadMethodCallException('Method "getTempFile" must be overridden by the extending class.');
	}

	protected static function assertRevertedOrderFixing(IntegrationCase $case, string $fixed_input_code, string $fixed_input_code_with_reversed_fixers): void
	{
		// If output is different depends on rules order - we need to verify that the rules are ordered by priority.
		// If not, any order is valid.
		if ($fixed_input_code !== $fixed_input_code_with_reversed_fixers) {
			self::assertGreaterThan(
				1,
				count(array_unique(array_map(
					static fn(FixerInterface $fixer): int => $fixer->getPriority(),
					self::createFixers($case),
				))),
				sprintf(
					'Rules priorities are not differential enough. If rules would be used in reverse order then final output would be different than the expected one. For that, different priorities must be set up for used rules to ensure stable order of them. In "%s".',
					$case->getFileName(),
				),
			);
		}
	}

	/**
	 * @return list<FixerInterface>
	 */
	private static function createFixers(IntegrationCase $case): array
	{
		$config = $case->getConfig();
		$custom_fixers = [];

		if (isset($config['customFixers'])) {
			foreach ($config['customFixers'] as $class_name) {
				$custom_fixers[] = new $class_name();
			}
		}

		return (new FixerFactory())
			->registerBuiltInFixers()
			->registerCustomFixers($custom_fixers)
			->useRuleSet($case->getRuleset())
			->setWhitespacesConfig(
				new WhitespacesFixerConfig($config['indent'], $config['lineEnding']),
			)
			->getFixers();
	}
}
