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
		global $tt ;echo'  '.(($tt??1000)/1000).'  ';

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
}
