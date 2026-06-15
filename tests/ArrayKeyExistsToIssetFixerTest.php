<?php

declare(strict_types=1);

use Live627\PhpCsFixer\CustomFixers\ArrayKeyExistsToIssetFixer;
use PhpCsFixer\AbstractFixer;
use PhpCsFixer\Tests\Test\AbstractFixerTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;

#[CoversClass(ArrayKeyExistsToIssetFixer::class)]
final class ArrayKeyExistsToIssetFixerTest extends AbstractFixerTestCase
{
	#[DataProvider('provideFixCases')]
	/****************
	 * Public methods
	 ****************/

	public function testFix(string $expected, ?string $input = null): void
	{
		$this->doTest($expected, $input);
	}

	/***********************
	 * Public static methods
	 ***********************/

	public static function provideFixCases(): iterable
	{
		yield 'simple string key' => [
			<<<'PHP'
				<?php

				isset($array['foo']);
				PHP,
			<<<'PHP'
				<?php

				array_key_exists('foo', $array);
				PHP,
		];

		yield 'variable key' => [
			<<<'PHP'
				<?php

				isset($data[$key]);
				PHP,
			<<<'PHP'
				<?php

				array_key_exists($key, $data);
				PHP,
		];

		yield 'inside if' => [
			<<<'PHP'
				<?php

				if (isset($data[$key])) {
					doSomething();
				}
				PHP,
			<<<'PHP'
				<?php

				if (array_key_exists($key, $data)) {
					doSomething();
				}
				PHP,
		];

		yield 'array access expression' => [
			<<<'PHP'
				<?php

				isset($items[$i]['name']);
				PHP,
			<<<'PHP'
				<?php

				array_key_exists('name', $items[$i]);
				PHP,
		];

		yield 'multiple occurrences' => [
			<<<'PHP'
				<?php

				isset($a['foo']);
				isset($b[$key]);
				PHP,
			<<<'PHP'
				<?php

				array_key_exists('foo', $a);
				array_key_exists($key, $b);
				PHP,
		];

		yield 'multiline call' => [
			<<<'PHP'
				<?php

				isset($data[$key]);
				PHP,
			<<<'PHP'
				<?php

				array_key_exists(
					$key,
					$data
				);
				PHP,
		];

		yield 'already fixed' => [
			<<<'PHP'
				<?php

				isset($data[$key]);
				PHP,
		];

		yield 'function call in key expression' => [
			"<?php\n\nisset(\$data[foo(\$a, \$b)]);",
			"<?php\n\narray_key_exists(foo(\$a, \$b), \$data);",
		];

		yield 'function call in array expression' => [
			"<?php\n\nisset(getData(\$a, \$b)['foo']);",
			"<?php\n\narray_key_exists('foo', getData(\$a, \$b));",
		];

		yield 'nested arrays in key expression' => [
			"<?php\n\nisset(\$data[[\$a, \$b][0]]);",
			"<?php\n\narray_key_exists([\$a, \$b][0], \$data);",
		];

		yield 'nested arrays in array expression' => [
			"<?php\n\nisset([\$a, \$b]['foo']);",
			"<?php\n\narray_key_exists('foo', [\$a, \$b]);",
		];

		yield 'nested function and array access' => [
			"<?php\n\nisset(getMap(\$a, foo(\$b, \$c))[bar(\$d, \$e)]);",
			"<?php\n\narray_key_exists(bar(\$d, \$e), getMap(\$a, foo(\$b, \$c)));",
		];

		yield 'ternary expression key' => [
			"<?php\n\nisset(\$data[\$cond ? foo(\$a, \$b) : bar(\$c, \$d)]);",
			"<?php\n\narray_key_exists(\$cond ? foo(\$a, \$b) : bar(\$c, \$d), \$data);",
		];

		yield 'closure in key expression' => [
			"<?php\n\nisset(\$data[function (\$a, \$b) { return \$a + \$b; }]);",
			"<?php\n\narray_key_exists(function (\$a, \$b) { return \$a + \$b; }, \$data);",
		];

		yield 'match expression key' => [
			"<?php\n\nisset(\$data[match (\$x) { 1 => foo(\$a, \$b), default => bar(\$c, \$d) }]);",
			"<?php\n\narray_key_exists(match (\$x) { 1 => foo(\$a, \$b), default => bar(\$c, \$d) }, \$data);",
		];

		yield 'nested commas on both sides' => [
			"<?php\n\nisset(getArray(foo(\$a, \$b), bar(\$c, \$d))[getKey(baz(\$e, \$f), qux(\$g, \$h))]);",
			"<?php\n\narray_key_exists(getKey(baz(\$e, \$f), qux(\$g, \$h)), getArray(foo(\$a, \$b), bar(\$c, \$d)));",
		];
	}

	/******************
	 * Internal methods
	 ******************/

	protected function createFixer(): AbstractFixer
	{
		return new ArrayKeyExistsToIssetFixer();
	}
}
