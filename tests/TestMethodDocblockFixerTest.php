<?php

declare(strict_types=1);

use Live627\PhpCsFixer\CustomFixers\TestMethodDocblockFixer;
use PhpCsFixer\AbstractFixer;
use PhpCsFixer\Tests\Test\AbstractFixerTestCase;
use PhpCsFixer\WhitespacesFixerConfig;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * @internal
 *
 * @covers \Live627\PhpCsFixer\CustomFixers\TestMethodDocblockFixer
 *
 * @extends AbstractFixerTestCase<\Live627\PhpCsFixer\CustomFixers\TestMethodDocblockFixer>
 *
 * @author John Rayes <live627@gmail.com>
 *
 * @no-named-arguments Parameter names are not covered by the backward compatibility promise.
 */
#[CoversClass(TestMethodDocblockFixer::class)]
final class TestMethodDocblockFixerTest extends AbstractFixerTestCase
{
	/****************
	 * Public methods
	 ****************/

	/**
	 * @dataProvider provideFixCases
	 */
	#[DataProvider('provideFixCases')]
	public function testFix(string $expected, ?string $input = null): void
	{
		$this->doTest($expected, $input);
	}

	public function testWithWhitespacesConfig(): void
	{
		$this->fixer->setWhitespacesConfig(new WhitespacesFixerConfig("\t", "\r\n"));

		$this->doTest(
			"<?php\r\n\r\nclass FooTest\r\n{\r\n\t/**\r\n\t * Foo.\r\n\t *\r\n\t * Expected: TODO describe the call and the assertion result.\r\n\t */\r\n\tpublic function testFoo(): void\r\n\t{\r\n\t}\r\n}\r\n",
			"<?php\r\n\r\nclass FooTest\r\n{\r\n\tpublic function testFoo(): void\r\n\t{\r\n\t}\r\n}\r\n",
		);

		$this->fixer->setWhitespacesConfig(new WhitespacesFixerConfig("\t", "\r\n"));

		$this->doTest(
			"<?php\r\n\r\nclass FooTest\r\n{\r\n\t/**\r\n\t * Foo.\r\n\t *\r\n\t * Expected: TODO describe the call and the assertion result.\r\n\t */\r\n\tpublic function testFoo(): void\r\n\t{\r\n\t}\r\n}\r\n",
			"<?php\r\n\r\nclass FooTest\r\n{\r\n\t/**\r\n\t * Foo.\r\n\t */\r\n\tpublic function testFoo(): void\r\n\t{\r\n\t}\r\n}\r\n",
		);
		$this->fixer->setWhitespacesConfig(new WhitespacesFixerConfig('    ', "\n"));

		$this->doTest(
			"<?php\n\nclass FooTest\n{\n    /**\n     * Foo.\n     *\n     * Expected: TODO describe the call and the assertion result.\n     */\n    public function testFoo(): void\n    {\n    }\n}\n",
			"<?php\n\nclass FooTest\n{\n    public function testFoo(): void\n    {\n    }\n}\n",
		);
	}

	/***********************
	 * Public static methods
	 ***********************/

	/**
	 * @return iterable<array{0: string, 1?: string}>
	 */
	public static function provideFixCases(): iterable
	{
		yield 'adds docblock to public test method' => [
			'<?php

class FooTest
{
	/**
	 * Something works.
	 *
	 * Expected: TODO describe the call and the assertion result.
	 */
	public function testSomethingWorks(): void
	{
	}
}
',
			'<?php

class FooTest
{
	public function testSomethingWorks(): void
	{
	}
}
',
		];

		yield 'method without explicit visibility' => [
			'<?php

class FooTest
{
	/**
	 * Foo.
	 *
	 * Expected: TODO describe the call and the assertion result.
	 */
	function testFoo(): void
	{
	}
}
',
			'<?php

class FooTest
{
	function testFoo(): void
	{
	}
}
',
		];

		yield 'docblock goes before modifiers' => [
			'<?php

class FooTest
{
	/**
	 * Foo.
	 *
	 * Expected: TODO describe the call and the assertion result.
	 */
	final public function testFoo(): void
	{
	}
}
',
			'<?php

class FooTest
{
	final public function testFoo(): void
	{
	}
}
',
		];

		yield 'docblock goes before attributes' => [
			'<?php

class FooTest
{
	/**
	 * Foo.
	 *
	 * Expected: TODO describe the call and the assertion result.
	 */
	#[DataProvider("provideFoo")]
	public function testFoo(int $x): void
	{
	}
}
',
			'<?php

class FooTest
{
	#[DataProvider("provideFoo")]
	public function testFoo(int $x): void
	{
	}
}
',
		];

		yield 'summary is built from underscores and camel case' => [
			'<?php

class FooTest
{
	/**
	 * Foo bar baz.
	 *
	 * Expected: TODO describe the call and the assertion result.
	 */
	public function testFoo_barBaz(): void
	{
	}
}
',
			'<?php

class FooTest
{
	public function testFoo_barBaz(): void
	{
	}
}
',
		];

		yield 'method named just test' => [
			'<?php

class FooTest
{
	/**
	 * TODO summary.
	 *
	 * Expected: TODO describe the call and the assertion result.
	 */
	public function test(): void
	{
	}
}
',
			'<?php

class FooTest
{
	public function test(): void
	{
	}
}
',
		];

		yield 'only methods without a docblock are documented' => [
			'<?php

class FooTest
{
	/**
	 * Already documented.
	 *
	 * Expected: Passes.
	 */
	public function testFirst(): void
	{
	}

	/**
	 * Second.
	 *
	 * Expected: TODO describe the call and the assertion result.
	 */
	public function testSecond(): void
	{
	}
}
',
			'<?php

class FooTest
{
	/**
	 * Already documented.
	 *
	 * Expected: Passes.
	 */
	public function testFirst(): void
	{
	}

	public function testSecond(): void
	{
	}
}
',
		];

		yield 'docblock goes after a section comment' => [
			'<?php

class FooTest
{
	/****************
	 * Public methods
	 ****************/

	/**
	 * Foo.
	 *
	 * Expected: TODO describe the call and the assertion result.
	 */
	public function testFoo(): void
	{
	}
}
',
			'<?php

class FooTest
{
	/****************
	 * Public methods
	 ****************/

	public function testFoo(): void
	{
	}
}
',
		];

		yield 'missing Expected section is added' => [
			'<?php

class FooTest
{
	/**
	 * Doc.
	 *
	 * Expected: TODO describe the call and the assertion result.
	 */
	public function testFoo(): void
	{
	}
}
',
			'<?php

class FooTest
{
	/**
	 * Doc.
	 */
	public function testFoo(): void
	{
	}
}
',
		];

		yield 'missing Expected section is added after a longer description' => [
			'<?php

class FooTest
{
	/**
	 * Doc.
	 *
	 * More detail.
	 *
	 * Expected: TODO describe the call and the assertion result.
	 */
	public function testFoo(): void
	{
	}
}
',
			'<?php

class FooTest
{
	/**
	 * Doc.
	 *
	 * More detail.
	 */
	public function testFoo(): void
	{
	}
}
',
		];

		yield 'missing Expected section is added before tags' => [
			'<?php

class FooTest
{
	/**
	 * Doc.
	 *
	 * Expected: TODO describe the call and the assertion result.
	 *
	 * @dataProvider provideFoo
	 */
	public function testFoo(int $x): void
	{
	}
}
',
			'<?php

class FooTest
{
	/**
	 * Doc.
	 *
	 * @dataProvider provideFoo
	 */
	public function testFoo(int $x): void
	{
	}
}
',
		];

		yield 'missing Expected section is added directly before tags without blank line' => [
			'<?php

class FooTest
{
	/**
	 * Doc.
	 *
	 * Expected: TODO describe the call and the assertion result.
	 *
	 * @dataProvider provideFoo
	 */
	public function testFoo(int $x): void
	{
	}
}
',
			'<?php

class FooTest
{
	/**
	 * Doc.
	 * @dataProvider provideFoo
	 */
	public function testFoo(int $x): void
	{
	}
}
',
		];

		yield 'missing summary and Expected section are added to tag-only docblock' => [
			'<?php

class FooTest
{
	/**
	 * Foo.
	 *
	 * Expected: TODO describe the call and the assertion result.
	 *
	 * @dataProvider provideFoo
	 */
	public function testFoo(int $x): void
	{
	}
}
',
			'<?php

class FooTest
{
	/**
	 * @dataProvider provideFoo
	 */
	public function testFoo(int $x): void
	{
	}
}
',
		];

		yield 'missing summary and Expected section are added to empty docblock' => [
			'<?php

class FooTest
{
	/**
	 * Foo.
	 *
	 * Expected: TODO describe the call and the assertion result.
	 */
	public function testFoo(): void
	{
	}
}
',
			'<?php

class FooTest
{
	/**
	 */
	public function testFoo(): void
	{
	}
}
',
		];

		yield 'single-line docblock is expanded' => [
			'<?php

class FooTest
{
	/**
	 * Doc.
	 *
	 * Expected: TODO describe the call and the assertion result.
	 */
	public function testFoo(): void
	{
	}
}
',
			'<?php

class FooTest
{
	/** Doc. */
	public function testFoo(): void
	{
	}
}
',
		];

		yield 'existing docblock is reindented' => [
			'<?php

class FooTest
{
	/**
	 * Doc.
	 *
	 * Expected: Passes.
	 */
	public function testFoo(): void
	{
	}
}
',
			'<?php

class FooTest
{
    /**
        * Doc.
        *
        * Expected: Passes.
      */
	public function testFoo(): void
	{
	}
}
',
		];

		yield 'reindented docblock also gets missing Expected section' => [
			'<?php

class FooTest
{
	/**
	 * Doc.
	 *
	 * Expected: TODO describe the call and the assertion result.
	 */
	public function testFoo(): void
	{
	}
}
',
			'<?php

class FooTest
{
    /**
        * Doc.
      */
	public function testFoo(): void
	{
	}
}
',
		];

		yield 'complete docblock is left alone' => [
			'<?php

class FooTest
{
	/**
	 * Doc.
	 *
	 * Expected: Passes.
	 */
	public function testFoo(): void
	{
	}
}
',
		];

		yield 'complete docblock before attributes is left alone' => [
			'<?php

class FooTest
{
	/**
	 * Doc.
	 *
	 * Expected: Passes.
	 */
	#[DataProvider("provideFoo")]
	public function testFoo(int $x): void
	{
	}
}
',
		];

		yield 'complete docblock between attribute and method is left alone' => [
			'<?php

class FooTest
{
	#[DataProvider("provideFoo")]
	/**
	 * Doc.
	 *
	 * Expected: Passes.
	 */
	public function testFoo(int $x): void
	{
	}
}
',
		];

		yield 'class not ending in Test is left alone' => [
			'<?php

class Foo
{
	public function testFoo(): void
	{
	}
}
',
		];

		yield 'non-test methods are left alone' => [
			'<?php

class FooTest
{
	public function helper(): void
	{
	}

	public function getTest(): void
	{
	}
}
',
		];

		yield 'protected and private test methods are left alone' => [
			'<?php

class FooTest
{
	protected function testFoo(): void
	{
	}

	private function testBar(): void
	{
	}
}
',
		];
	}

	/******************
	 * Internal methods
	 ******************/

	protected function createFixer(): AbstractFixer
	{
		$fixer = new TestMethodDocblockFixer();
		$fixer->setWhitespacesConfig(new WhitespacesFixerConfig("\t", "\n"));

		return $fixer;
	}
}
