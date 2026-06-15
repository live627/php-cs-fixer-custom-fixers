<?php

declare(strict_types=1);

use PhpCsFixer\Fixer\ConfigurableFixerInterface;
use SebastianBergmann\Diff\Differ;
use SebastianBergmann\Diff\Output\UnifiedDiffOutputBuilder;

require __DIR__ . '/vendor/autoload.php';

$src_dir = __DIR__ . '/src';
$docs_dir = __DIR__ . '/docs/rules';

@mkdir($docs_dir, 0777, true);

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
			$markdown[] = var_export($option->getDefault(), true);
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
			$markdown[] = var_export($sample->getConfiguration(), true);
			$markdown[] = '```';
			$markdown[] = '';
		}

		$markdown[] = '```diff';
		$markdown[] = rtrim($diff);
		$markdown[] = '```';
		$markdown[] = '';
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
