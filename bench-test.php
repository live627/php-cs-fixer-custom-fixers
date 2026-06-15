<?php

declare(strict_types=1);

use Live627\PhpCsFixer\CustomFixers\SnakeCaseIdentifiersFixer;
use PhpCsFixer\Tokenizer\Tokens;

require __DIR__ . '/vendor/autoload.php';

$classes = [
	[
		'Live627\PhpCsFixer\CustomFixers\SectionCommentsFixer',
		'SectionCommentsFixerTest',
	],
	[
		'Live627\PhpCsFixer\CustomFixers\ArrayKeyExistsToIssetFixer',
		'ArrayKeyExistsToIssetFixerTest',
	],
	[
		'Live627\PhpCsFixer\CustomFixers\GlobalNativeNamespaceImportFixer',
		'GlobalNativeNamespaceImportFixerTest',
	],
];

$lastFixer = null;

foreach ($classes as [$class, $test]) {
	$fixer = new $class();
	require __DIR__ . '/tests/' . $test . '.php';

	foreach ($test::provideFixCases() as $key => $code) {
		$tokens = PhpCsFixer\Tokenizer\Tokens::fromCode($code[1] ?? $code[0]);

		$iterations = 1000;
		$times = [];
		$warmups = 10;
		$timerSamples = [];

		for ($i = 0; $i < $iterations + $warmups; ++$i)
		{
			$f = new SplFileInfo('test.php');
			$t = clone $tokens;

			$time = -hrtime(true);

			$fixer->fix($f, $t);

			$time += hrtime(true);

			if ($i >= $warmups) {
				$times[] = $time;
			}

			if ($i >= $warmups && property_exists($fixer, 'timer')) {
				foreach ($fixer->timer as $section => $sectionTime) {
					$timerSamples[$section][] = $sectionTime;
					$fixer->timer[$section] = 0;
				}
			}
		}

		sort($times);

		$min = $times[0];
		$max = $times[count($times) - 1];
		$avg = array_sum($times) / count($times);
		$median = $times[(int) floor(count($times) / 2)];

		$variance = 0.0;

		foreach ($times as $time) {
			$variance += ($time - $avg) ** 2;
		}

		$stddev = sqrt($variance / count($times));

		$fixerName = (new ReflectionClass($fixer))->getShortName();

		if ($fixerName !== $lastFixer) {
			if ($lastFixer !== null) {
				echo "\n";
			}

			echo "\n";
			echo $fixerName . "\n";
			echo str_repeat('=', strlen($fixerName)) . "\n\n";

			printf(
				"%-40s %8s %10s %10s %10s %8s\n",
				'Case',
				'Tokens',
				'Median',
				'Avg',
				'Max',
				'CV%'
			);

			echo str_repeat('-', 95) . "\n";

			$lastFixer = $fixerName;
		}

		printf(
			"%-40s %8d %10.2f %10.2f %10.2f %8.2f\n",
			is_string($key) ? $key : "#{$key}",
			count($tokens),
			$median / 1e3,
			$avg / 1e3,
			$max / 1e3,
			($stddev / $avg) * 100,
		);
	}

if ($timerSamples !== []) {
	echo "\nSection Timings\n";
	echo "===============\n\n";

	$stats = [];

	foreach ($timerSamples as $section => $samples) {
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
		static fn (array $a, array $b): int => $b['avg'] <=> $a['avg'],
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
