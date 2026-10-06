<?php

declare(strict_types=1);

use PhpCsFixer\Fixer\ConfigurableFixerInterface;
use SebastianBergmann\Diff\Differ;
use SebastianBergmann\Diff\Output\UnifiedDiffOutputBuilder;

/**
 * Exports a PHP value using short array syntax.
 */
function shortVarExport(mixed $value, int $indent = 0): string
{
	if (!is_array($value)) {
		return var_export($value, true);
	}

	if ($value === []) {
		return '[] // empty array';
	}

	$padding = str_repeat('    ', $indent);
	$child_padding = str_repeat('    ', $indent + 1);
	$is_list = array_is_list($value);

	$lines = ['['];

	foreach ($value as $key => $item) {
		$line = $child_padding;

		if (!$is_list) {
			$line .= var_export($key, true) . ' => ';
		}

		$line .= shortVarExport($item, $indent + 1) . ',';

		$lines[] = $line;
	}

	$lines[] = $padding . ']';

	return implode("\n", $lines);
}

require __DIR__ . '/vendor/autoload.php';

$src_dir = __DIR__ . '/src';
$docs_dir = __DIR__ . '/docs/rules';

@mkdir($docs_dir, 0777, true);
$integration_tests = findIntegrationTests(__DIR__ . '/tests');
$iterator = new RecursiveIteratorIterator(
	new RecursiveDirectoryIterator($src_dir),
);
$fixers = [];

foreach ($iterator as $file) {
	if (!$file->isFile() || $file->getExtension() !== 'php') {
		continue;
	}

	$relative = str_replace(
		[$src_dir . DIRECTORY_SEPARATOR, '.php', DIRECTORY_SEPARATOR],
		['', '', '\\'],
		$file->getPathname(),
	);

	$class = 'Live627\\PhpCsFixer\\CustomFixers\\' . $relative;

	if (!class_exists($class)) {
		require_once $file->getPathname();
	}

	if (!class_exists($class)) {
		continue;
	}

	$fixer = new $class();

	if (!$fixer instanceof PhpCsFixer\Fixer\FixerInterface) {
		continue;
	}

	$definition = $fixer->getDefinition();
	$rule_name = $fixer->getName();
	$filename = preg_replace(
		'/[^a-z0-9]+/',
		'-',
		strtolower(substr($rule_name, strpos($rule_name, '/') + 1)),
	);

	$fixers[] = [
		'name' => $fixer->getName(),
		'class' => $fixer::class,
		'file' => $filename . '.md',
		'summary' => $definition->getSummary(),
	];

	$markdown = [];

	$markdown[] = '# Rule `' . $rule_name . '`';
	$markdown[] = '';

	$markdown[] = $definition->getSummary();
	$markdown[] = '';

	$markdown[] = '## Priority';
	$markdown[] = '';
	$markdown[] = 'This fixer has priority `' . $fixer->getPriority() . '`. Higher priorities are executed first.';
	$markdown[] = '';

	$dependencies = getPriorityDependencies(
		(new ReflectionClass($fixer))->getMethod('getPriority'),
	);

	foreach (['before' => 'Must run before', 'after' => 'Must run after'] as $type => $heading) {
		$entries = [];

		foreach ($dependencies[$type] as $dependency) {
			$fixer_class = findFixerClass(
				$dependency,
				__DIR__ . '/vendor/friendsofphp/php-cs-fixer/src/Fixer',
			);
			$priority = (new $fixer_class())->getPriority();

			$entries[] = '- `' . $dependency . '` — priority `' . $priority . '`';
		}

		if ($entries === []) {
			continue;
		}

		$markdown[] = '**' . $heading . ':**';
		$markdown[] = '';
		$markdown = [...$markdown, ...$entries];
		$markdown[] = '';
	}

	if ($fixer->isRisky()) {
		$markdown[] = '## Warning';
		$markdown[] = '';
		$markdown[] = 'This rule is risky.';
		$markdown[] = '';

		if ($definition->getRiskyDescription() !== null) {
			$markdown[] = $definition->getRiskyDescription();
			$markdown[] = '';
		}
	}

	if ($fixer instanceof ConfigurableFixerInterface) {
		$markdown[] = '## Configuration';
		$markdown[] = '';

		$resolver = (new ReflectionClass($fixer))
			->getMethod('createConfigurationDefinition');

		$config = $resolver->invoke($fixer);

		foreach ($config->getOptions() as $option) {
			$markdown[] = '### `' . $option->getName() . '`';
			$markdown[] = '';
			$markdown[] = $option->getDescription();
			$markdown[] = '';

			$markdown[] = '**Default:**';
			$markdown[] = '';
			$markdown[] = '```php';
			$markdown[] = shortVarExport($option->getDefault());
			$markdown[] = '```';
			$markdown[] = '';
		}
	}

	$markdown[] = '## Examples';
	$markdown[] = '';

	foreach ($definition->getCodeSamples() as $index => $sample) {
		$markdown[] = '### Example #' . ($index + 1);
		$markdown[] = '';

		$clone = clone $fixer;

		if (
			$clone instanceof ConfigurableFixerInterface
			&& $sample->getConfiguration() !== null
		) {
			$clone->configure($sample->getConfiguration());
		}

		$input = $sample->getCode();

		$tokens = PhpCsFixer\Tokenizer\Tokens::fromCode($input);

		$clone->fix(
			new SplFileInfo('sample.php'),
			$tokens,
		);

		$output = $tokens->generateCode();

		$differ = new Differ(
			new UnifiedDiffOutputBuilder(
				"--- Original\n+++ Fixed\n",
			),
		);

		$diff = $differ->diff($input, $output);

		if ($sample->getConfiguration() !== null) {
			$markdown[] = '**Configuration**';
			$markdown[] = '';
			$markdown[] = '```php';
			$markdown[] = shortVarExport($sample->getConfiguration());
			$markdown[] = '```';
			$markdown[] = '';
		}

		$markdown[] = '```diff';
		$markdown[] = rtrim($diff);
		$markdown[] = '```';
		$markdown[] = '';
	}

	$test_cases = findFixerTestCases(__DIR__ . '/tests', $fixer::class);
	$test_names = [
		'Live627/array_key_exists_to_isset' => [
			'simple string key',
			'variable key',
			'inside if',
			'array access expression',
			'function call in key expression',
			'function call in array expression',
		],
		'Live627/global_native_namespace_import' => [
			'new expression',
			'extends',
			'implements',
			'instanceof',
			'catch',
			'parameter type',
			'return type',
			'property type',
			'union type',
			'intersection type',
			'static call',
			'class constant',
		],
		'SMF/section_comments' => [
			'misplaced and duplicate section comments',
			'completely wrong section comments',
			'normalizes whitespace around existing section comments',
			'does not add blank line before first section comment',
			'attributes remain attached to method',
		],
		'Live627/snake_case_identifiers' => [
			'phpdoc rename',
			'multiple variables',
			'phpdoc and variables',
		],
	];

	if ($test_cases !== [])
	{
		$markdown[] = '## Test Cases';
		$markdown[] = '';
		$markdown[] = 'These examples are taken from the fixer\'s PHPUnit test cases.';
		$markdown[] = '';
		$markdown[] = formatTestCases($test_cases, $fixer->getName(), $test_names);
	}

	$advanced_examples = [];

	foreach ($integration_tests[$fixer->getName()] ?? [] as $file => $test)
	{
		$advanced_examples[$file] = $test;
	}

	$advanced_examples = array_slice($advanced_examples, 0, 3);

	if ($advanced_examples !== [])
	{
		$markdown[] = '## Advanced Examples';
		$markdown[] = '';
		$markdown[] = 'These examples demonstrate how this fixer interacts with other fixers in an integration test.';
		$markdown[] = '';

		foreach ($advanced_examples as $test)
		{
			$markdown[] = formatIntegrationTest($test);
		}
	}

	$markdown[] = '## References';
	$markdown[] = '';

	$reflection = new ReflectionClass($fixer);

	$class_path = str_replace(
		__DIR__ . DIRECTORY_SEPARATOR,
		'',
		$reflection->getFileName(),
	);

	$test_path = 'tests/' . $reflection->getShortName() . 'Test.php';

	$markdown[] = '- Class: [`' . $reflection->getName() . '`](../' . $class_path . ')';
	$markdown[] = '  - `' . $class_path . '`';

	if (is_file(__DIR__ . '/' . $test_path)) {
		$markdown[] = '- Test: [`' . $reflection->getShortName() . 'Test`](../' . $test_path . ')';
		$markdown[] = '  - `' . $test_path . '`';
	}

	$markdown[] = '';

	file_put_contents(
		$docs_dir . '/' . $filename . '.md',
		implode("\n", $markdown),
	);

	echo "Generated {$filename}.md\n";
}

usort(
	$fixers,
	static fn(array $a, array $b): int => strcmp($a['name'], $b['name']),
);

$rules = [];

foreach ($fixers as $fixer) {
	$rules[] = sprintf(
		"- [`%s`](docs/rules/%s) (`%s`)\n   - %s\n",
		$fixer['name'],
		$fixer['file'],
		$fixer['class'],
		$fixer['summary'],
	);
}

$replacement = implode(
	"\n",
	[
		'<!-- BEGIN AUTO-GENERATED RULES -->',
		'',
		'## Available Rules',
		'',
		...$rules,
		'<!-- END AUTO-GENERATED RULES -->',
	],
);

$readme_file = __DIR__ . '/README.md';
$readme = file_get_contents($readme_file);

$readme = preg_replace(
	'/<!-- BEGIN AUTO-GENERATED RULES -->.*?<!-- END AUTO-GENERATED RULES -->/s',
	$replacement,
	$readme,
);

file_put_contents($readme_file, $readme);

/**
 * Extracts fixer names from a priority docblock.
 *
 * @return array{before: list<string>, after: list<string>}
 */
function getPriorityDependencies(ReflectionMethod $method): array
{
	$result = [
		'before' => [],
		'after' => [],
	];

	$doc_comment = $method->getDocComment();

	if ($doc_comment === false) {
		return $result;
	}

	foreach (explode("\n", $doc_comment) as $line) {
		$line = trim($line, " \t*");

		if (str_starts_with($line, 'Must run before ')) {
			$result['before'] = array_map(
				trim(...),
				explode(',', rtrim(substr($line, 16), '.')),
			);
		} elseif (str_starts_with($line, 'Must run after ')) {
			$result['after'] = array_map(
				trim(...),
				explode(',', rtrim(substr($line, 15), '.')),
			);
		}
	}

	return $result;
}

/**
 * Finds a PHP-CS-Fixer class by fixer filename.
 *
 * @return class-string|null
 */
function findFixerClass(string $fixer_name, string $directory): ?string
{
	if (!str_ends_with($fixer_name, '.php')) {
		$fixer_name .= '.php';
	}

	$directory = rtrim($directory, DIRECTORY_SEPARATOR);

	$iterator = new RecursiveIteratorIterator(
		new RecursiveDirectoryIterator(
			$directory,
			FilesystemIterator::SKIP_DOTS,
		),
	);

	foreach ($iterator as $file) {
		if (!$file->isFile() || $file->getFilename() !== $fixer_name) {
			continue;
		}

		$relative = substr(
			$file->getPathname(),
			strlen($directory) + 1,
		);

		return 'PhpCsFixer\\Fixer\\' . str_replace(
			[DIRECTORY_SEPARATOR, '.php'],
			['\\', ''],
			$relative,
		);
	}

	return null;
}

/**
 * Finds integration test fixtures and indexes them by fixer name.
 *
 * A fixture is associated with a fixer when the fixer appears in its
 * RULESET section or its fully qualified class name appears in CONFIG.
 *
 * @param string $tests_dir The tests directory.
 *
 * @return array<string, array<string, array{
 *     file: string,
 *     test: string,
 *     ruleset: array,
 *     config: array,
 *     input: string,
 *     expect: string
 * }>>
 */
function findIntegrationTests(string $tests_dir): array
{
	$integration_tests = [];

	$iterator = new RecursiveIteratorIterator(
		new RecursiveDirectoryIterator(
			$tests_dir,
			FilesystemIterator::SKIP_DOTS,
		),
	);

	foreach ($iterator as $file)
	{
		if (!$file->isFile() || $file->getExtension() !== 'test')
		{
			continue;
		}

		$contents = file_get_contents($file->getPathname());

		if ($contents === false)
		{
			continue;
		}

		$sections = parseIntegrationTestSections($contents);

		if (!isset($sections['TEST'], $sections['RULESET'], $sections['EXPECT']))
		{
			continue;
		}

		try
		{
			$ruleset = json_decode(
				$sections['RULESET'],
				true,
				512,
				JSON_THROW_ON_ERROR,
			);

			$config = isset($sections['CONFIG'])
				? json_decode(
					$sections['CONFIG'],
					true,
					512,
					JSON_THROW_ON_ERROR,
				)
				: [];
		}
		catch (JsonException)
		{
			continue;
		}

		if (!is_array($ruleset) || !is_array($config))
		{
			continue;
		}

		$test = [
			'file' => $file->getPathname(),
			'test' => trim($sections['TEST']),
			'ruleset' => $ruleset,
			'config' => $config,
			'input' => $sections['INPUT'] ?? $sections['EXPECT'],
			'expect' => $sections['EXPECT'],
		];

		$rule_names = array_keys($ruleset);

		foreach ($rule_names as $rule_name)
		{
			$integration_tests[$rule_name][$file->getPathname()] = $test;
		}

		foreach ($config['customFixers'] ?? [] as $class_name)
		{
			$integration_tests[$class_name][$file->getPathname()] = $test;
		}
	}

	return $integration_tests;
}

/**
 * Parses the sections from an integration test fixture.
 *
 * @param string $contents The fixture contents.
 *
 * @return array<string, string>
 */
function parseIntegrationTestSections(string $contents): array
{
	preg_match_all(
		'/^--([A-Z][A-Z0-9_]*)--\R(.*?)(?=^--[A-Z][A-Z0-9_]*--|\z)/ms',
		$contents,
		$matches,
		PREG_SET_ORDER,
	);

	$sections = [];

	foreach ($matches as $match)
	{
		$sections[$match[1]] = rtrim($match[2]);
	}

	return $sections;
}

/**
 * Formats an integration test as advanced documentation.
 *
 * @param array{
 *     file: string,
 *     test: string,
 *     ruleset: array,
 *     config: array,
 *     input: string,
 *     expect: string
 * } $test The integration test.
 *
 * @return string The generated Markdown.
 */
function formatIntegrationTest(array $test): string
{
	static $i = 1;

	$markdown = '### Integration example # ' . ($i++) . "\n\n";

	$markdown .= '**Ruleset:**' . "\n\n";
	$markdown .= "```php\n";
	$markdown .= shortVarExport($test['ruleset']);
	$markdown .= "\n```\n\n";

	$differ = new Differ(
		new UnifiedDiffOutputBuilder(
			"--- Original\n+++ Fixed\n",
		),
	);

	$diff = $differ->diff($test['input'], $test['expect']);

	$markdown .= "```diff\n";
	$markdown .= trim($diff) . "\n";
	$markdown .= "```\n\n";

	return $markdown;
}

use PhpCsFixer\Tests\Test\AbstractFixerTestCase;

/**
 * Finds the PHPUnit test class for a fixer.
 *
 * @param string $tests_dir The tests directory.
 * @param string $fixer_class The fully qualified fixer class.
 *
 * @return string|null The test class name.
 */
function findFixerTestClass(string $tests_dir, string $fixer_class): ?string
{
	$fixer_name = basename(str_replace('\\', '/', $fixer_class));
	$test_file_name = $fixer_name . 'Test.php';

	$iterator = new RecursiveIteratorIterator(
		new RecursiveDirectoryIterator(
			$tests_dir,
			FilesystemIterator::SKIP_DOTS,
		),
	);

	foreach ($iterator as $file)
	{
		if (!$file->isFile() || $file->getFilename() !== $test_file_name)
		{
			continue;
		}

		$before = get_declared_classes();

		require_once $file->getPathname();

		$new_classes = array_diff(get_declared_classes(), $before);

		foreach ($new_classes as $class_name)
		{
			if (
				str_ends_with($class_name, 'Test')
				&& is_subclass_of($class_name, AbstractFixerTestCase::class)
			)
			{
				return $class_name;
			}
		}
	}

	return null;
}

/**
 * Gets examples from a fixer's PHPUnit data provider.
 *
 * @param string $tests_dir The tests directory.
 * @param string $fixer_class The fully qualified fixer class.
 *
 * @return array<string, array{expected: string, input: string|null}>
 */
function findFixerTestCases(
	string $tests_dir,
	string $fixer_class,
): array
{
	$test_class = findFixerTestClass($tests_dir, $fixer_class);

	if ($test_class === null || !method_exists($test_class, 'provideFixCases'))
	{
		return [];
	}

	$cases = [];

	try
	{
		foreach ($test_class::provideFixCases() as $name => $case)
		{
			if (!is_array($case) || !isset($case[0]))
			{
				continue;
			}

			$cases[(string) $name] = [
				'expected' => $case[0],
				'input' => $case[1] ?? null,
			];
		}
	}
	catch (Throwable)
	{
		return [];
	}

	return $cases;
}

/**
 * Creates a unified diff between the input and expected output.
 *
 * @param string $input The input code.
 * @param string $expected The expected code.
 *
 * @return string The generated diff.
 */
function createTestCaseDiff(string $input, string $expected): string
{
	$input_lines = explode("\n", rtrim($input, "\n"));
	$expected_lines = explode("\n", rtrim($expected, "\n"));

	$diff = [];
	$max_lines = max(count($input_lines), count($expected_lines));

	for ($i = 0; $i < $max_lines; ++$i)
	{
		$input_line = $input_lines[$i] ?? null;
		$expected_line = $expected_lines[$i] ?? null;

		if ($input_line === $expected_line)
		{
			$diff[] = '  ' . ($input_line ?? '');

			continue;
		}

		if ($input_line !== null)
		{
			$diff[] = '- ' . $input_line;
		}

		if ($expected_line !== null)
		{
			$diff[] = '+ ' . $expected_line;
		}
	}

	return implode("\n", $diff);
}

/**
 * Formats PHPUnit test cases as documentation.
 *
 * @param array<string, array{expected: string, input: string|null}> $test_cases
 *
 * @return string The generated Markdown.
 */
function formatTestCases(array $test_cases, string $test_name, array $test_names): string
{
	$markdown = '';

	foreach ($test_cases as $name => $case)
	if (in_array($name, $test_names[$test_name]))
	{
		$input = $case['input'] ?? $case['expected'];

		$markdown .= '### ' . $name . "\n\n";

		$differ = new Differ(
			new UnifiedDiffOutputBuilder(
				"--- Original\n+++ Fixed\n",
			),
		);

		$markdown .= "```diff\n";
		$markdown .= rtrim($differ->diff($input . "\n", $case['expected'] . "\n"));
		$markdown .= "\n```\n\n";
	}

	return $markdown;
}
