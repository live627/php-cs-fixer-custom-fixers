<?php

declare(strict_types=1);

use PhpCsFixer\Fixer\ConfigurableFixerInterface;
use PhpCsFixer\FixerDefinition\CodeSampleInterface;
use SebastianBergmann\Diff\Differ;
use SebastianBergmann\Diff\Output\UnifiedDiffOutputBuilder;

require __DIR__ . '/vendor/autoload.php';

$srcDir = __DIR__ . '/src';
$docsDir = __DIR__ . '/docs/rules';

@mkdir($docsDir, 0777, true);

$iterator = new RecursiveIteratorIterator(
	new RecursiveDirectoryIterator($srcDir),
);

foreach ($iterator as $file) {
	if (!$file->isFile() || $file->getExtension() !== 'php') {
		continue;
	}

	$relative = str_replace(
		[$srcDir . DIRECTORY_SEPARATOR, '.php', DIRECTORY_SEPARATOR],
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

	$ruleName = $fixer->getName();
	$filename = preg_replace('/[^a-z0-9]+/', '-', strtolower($ruleName));

	$markdown = [];

	$markdown[] = '# Rule `' . $ruleName . '`';
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
				"--- Original\n+++ Fixed\n"
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

	file_put_contents(
		$docsDir . '/' . $filename . '.md',
		implode("\n", $markdown),
	);

	echo "Generated {$filename}.md\n";
}
