<?php

declare(strict_types=1);

use PhpCsFixer\Tokenizer\Tokens;

require __DIR__ . '/vendor/autoload.php';

$fixer = new Live627\PhpCsFixer\CustomFixers\GlobalNativeNamespaceImportFixer();

$files = array_slice($argv, 1);

if ($files === []) {
	fwrite(STDERR, "Usage: php benchmark.php file1.php [file2.php ...]\n");

	exit(1);
}

$iterations = 100;
$warmups = 10;

printf(
	"%-60s %8s %10s %10s %10s %8s\n",
	'File',
	'Tokens',
	'Median',
	'Avg',
	'Max',
	'CV%',
);

echo str_repeat('-', 115) . "\n";

foreach ($files as $file) {
	if (!is_file($file)) {
		fprintf(STDERR, "Skipping missing file: %s\n", $file);

		continue;
	}

	$tokens = Tokens::fromCode(file_get_contents($file));

	$times = [];
	$timer_totals = [];
	$timer_samples = [];

	for ($i = 0; $i < $iterations + $warmups; ++$i) {
		$f = new SplFileInfo($file);
		$t = clone $tokens;

		$time = -hrtime(true);

		$fixer->fix($f, $t);

		$time += hrtime(true);

		if ($i >= $warmups) {
			$times[] = $time;
		}

		if ($i >= $warmups && property_exists($fixer, 'timer')) {
			foreach ($fixer->timer as $section => $section_time) {
				$timer_samples[$section][] = $section_time;
				$fixer->timer[$section] = 0;
			}
		}
	}

	sort($times);

	$avg = array_sum($times) / count($times);
	$median = $times[(int) floor(count($times) / 2)];
	$max = $times[array_key_last($times)];

	$variance = 0.0;

	foreach ($times as $time) {
		$variance += ($time - $avg) ** 2;
	}

	$stddev = sqrt($variance / count($times));

	printf(
		"%-60s %8d %10.2f %10.2f %10.2f %8.2f\n",
		basename($file),
		count($tokens),
		$median / 1e3,
		$avg / 1e3,
		$max / 1e3,
		($stddev / $avg) * 100,
	);

	if ($timer_samples !== []) {
		echo "\nSection Timings\n";
		echo "===============\n\n";

		$stats = [];

		foreach ($timer_samples as $section => $samples) {
			sort($samples);

			$avg = array_sum($samples) / count($samples);
			$median = $samples[(int) floor(count($samples) / 2)];

			$stats[$section] = [
				'avg' => $avg,
				'median' => $median,
			];
		}

		uasort(
			$stats,
			static fn(array $a, array $b): int => $b['avg'] <=> $a['avg'],
		);

		printf(
			"%-30s %14s %14s\n",
			'Section',
			'Median (µs)',
			'Avg (µs)',
		);

		echo str_repeat('-', 60) . "\n";

		foreach ($stats as $section => $data) {
			printf(
				"%-30s %14.2f %14.2f\n",
				$section,
				$data['median'] / 1e3,
				$data['avg'] / 1e3,
			);
		}

		echo "\n";
	}
}
