<?php

declare(strict_types=1);

use Live627\PhpCsFixer\CustomFixers\SectionCommentsFixer;
use PhpCsFixer\AbstractFixer;
use PhpCsFixer\Tests\Test\AbstractFixerTestCase;
use PhpCsFixer\WhitespacesFixerConfig;
use PHPUnit\Framework\Attributes\DataProvider;

final class SectionCommentsFixerTest extends AbstractFixerTestCase
{
	/****************
	 * Public methods
	 ****************/

	#[DataProvider('provideFixCases')]
	public function testFix(string $expected, ?string $input = null): void
	{
		$this->doTest($expected, $input);
	}

	public function testWithWhitespacesConfig(): void
	{
		$this->fixer->setWhitespacesConfig(new WhitespacesFixerConfig("\t", "\r\n"));

		$expected = <<<'PHP'
			<?php

			class Foo
			{
				/*******************
				 * Public properties
				 *******************/

				public string $a;

				/****************
				 * Public methods
				 ****************/

				public function b(): void {}
			}
			PHP;

		$input = <<<'PHP'
			<?php

			class Foo
			{
				public string $a;

				public function b(): void {}
			}
			PHP;

		$this->doTest(
			str_replace("\n", "\r\n", $expected),
			str_replace("\n", "\r\n", $input),
		);

		$this->fixer->setWhitespacesConfig(new WhitespacesFixerConfig("\t", "\n"));

		$this->doTest(
			<<<'PHP'
				<?php

				class A
				{
					/*******************
					 * Public properties
					 *******************/

					public $a;
				}
				PHP,
			<<<'PHP'
				<?php

				class A
				{public $a;
				}
				PHP,
		);
	}

	/***********************
	 * Public static methods
	 ***********************/

	public static function provideFixCases(): iterable
	{
		yield 'all sections with phpdocs and enum' => [
			<<<'PHP'
				<?php

				enum Status
				{
					/************
					 * Enum cases
					 ************/

					/**
					 * Draft.
					 */
					case Draft;

					/**
					 * Published.
					 */
					case Published;

					/**
					 * Archived.
					 */
					case Archived;
				}

				class Foo
				{
					/*****************
					 * Class constants
					 *****************/

					/**
					 * Constant 1.
					 */
					public const CONST_ONE = 1;

					/**
					 * Constant 2.
					 */
					public const CONST_TWO = 2;

					/**
					 * Constant 3.
					 */
					public const CONST_THREE = 3;

					/*******************
					 * Public properties
					 *******************/

					/**
					 * Property 1.
					 */
					public string $publicOne;

					/**
					 * Property 2.
					 */
					public string $publicTwo;

					/**
					 * Property 3.
					 */
					public string $publicThree;

					/**************************
					 * Public static properties
					 **************************/

					/**
					 * Static property 1.
					 */
					public static string $publicStaticOne;

					/**
					 * Static property 2.
					 */
					public static string $publicStaticTwo;

					/**
					 * Static property 3.
					 */
					public static string $publicStaticThree;

					/*********************
					 * Internal properties
					 *********************/

					/**
					 * Protected property.
					 */
					protected string $protectedOne;

					/**
					 * Private property.
					 */
					private string $privateOne;

					/**
					 * Protected property.
					 */
					protected string $protectedTwo;

					/****************************
					 * Internal static properties
					 ****************************/

					/**
					 * Protected static property.
					 */
					protected static string $protectedStaticOne;

					/**
					 * Private static property.
					 */
					private static string $privateStaticOne;

					/**
					 * Protected static property.
					 */
					protected static string $protectedStaticTwo;

					/****************
					 * Public methods
					 ****************/

					/**
					 * Method 1.
					 */
					public function publicOne(): void {}

					/**
					 * Method 2.
					 */
					public function publicTwo(): void {}

					/**
					 * Method 3.
					 */
					public function publicThree(): void {}

					/***********************
					 * Public static methods
					 ***********************/

					/**
					 * Static method 1.
					 */
					public static function publicStaticOne(): void {}

					/**
					 * Static method 2.
					 */
					public static function publicStaticTwo(): void {}

					/**
					 * Static method 3.
					 */
					public static function publicStaticThree(): void {}

					/******************
					 * Internal methods
					 ******************/

					/**
					 * Protected method.
					 */
					protected function protectedOne(): void {}

					/**
					 * Private method.
					 */
					private function privateOne(): void {}

					/**
					 * Protected method.
					 */
					protected function protectedTwo(): void {}

					/*************************
					 * Internal static methods
					 *************************/

					/**
					 * Protected static method.
					 */
					protected static function protectedStaticOne(): void {}

					/**
					 * Private static method.
					 */
					private static function privateStaticOne(): void {}

					/**
					 * Protected static method.
					 */
					protected static function protectedStaticTwo(): void {}
				}
				PHP,
			<<<'PHP'
				<?php

				enum Status
				{
					/**
					 * Draft.
					 */
					case Draft;

					/**
					 * Published.
					 */
					case Published;

					/**
					 * Archived.
					 */
					case Archived;
				}

				class Foo
				{
					/**
					 * Constant 1.
					 */
					public const CONST_ONE = 1;

					/**
					 * Constant 2.
					 */
					public const CONST_TWO = 2;

					/**
					 * Constant 3.
					 */
					public const CONST_THREE = 3;

					/**
					 * Property 1.
					 */
					public string $publicOne;

					/**
					 * Property 2.
					 */
					public string $publicTwo;

					/**
					 * Property 3.
					 */
					public string $publicThree;

					/**
					 * Static property 1.
					 */
					public static string $publicStaticOne;

					/**
					 * Static property 2.
					 */
					public static string $publicStaticTwo;

					/**
					 * Static property 3.
					 */
					public static string $publicStaticThree;

					/**
					 * Protected property.
					 */
					protected string $protectedOne;

					/**
					 * Private property.
					 */
					private string $privateOne;

					/**
					 * Protected property.
					 */
					protected string $protectedTwo;

					/**
					 * Protected static property.
					 */
					protected static string $protectedStaticOne;

					/**
					 * Private static property.
					 */
					private static string $privateStaticOne;

					/**
					 * Protected static property.
					 */
					protected static string $protectedStaticTwo;

					/**
					 * Method 1.
					 */
					public function publicOne(): void {}

					/**
					 * Method 2.
					 */
					public function publicTwo(): void {}

					/**
					 * Method 3.
					 */
					public function publicThree(): void {}

					/**
					 * Static method 1.
					 */
					public static function publicStaticOne(): void {}

					/**
					 * Static method 2.
					 */
					public static function publicStaticTwo(): void {}

					/**
					 * Static method 3.
					 */
					public static function publicStaticThree(): void {}

					/**
					 * Protected method.
					 */
					protected function protectedOne(): void {}

					/**
					 * Private method.
					 */
					private function privateOne(): void {}

					/**
					 * Protected method.
					 */
					protected function protectedTwo(): void {}

					/**
					 * Protected static method.
					 */
					protected static function protectedStaticOne(): void {}

					/**
					 * Private static method.
					 */
					private static function privateStaticOne(): void {}

					/**
					 * Protected static method.
					 */
					protected static function protectedStaticTwo(): void {}
				}
				PHP,
		];

		yield 'all sections and enum' => [
			<<<'PHP'
				<?php

				enum Status
				{
					/************
					 * Enum cases
					 ************/

					case Draft;

					case Published;

					case Archived;
				}

				class Foo
				{
					/*****************
					 * Class constants
					 *****************/

					public const CONST_ONE = 1;

					public const CONST_TWO = 2;

					public const CONST_THREE = 3;

					/*******************
					 * Public properties
					 *******************/

					public string $publicOne;

					public string $publicTwo;

					public string $publicThree;

					/**************************
					 * Public static properties
					 **************************/

					public static string $publicStaticOne;

					public static string $publicStaticTwo;

					public static string $publicStaticThree;

					/*********************
					 * Internal properties
					 *********************/

					protected string $protectedOne;

					private string $privateOne;

					protected string $protectedTwo;

					/****************************
					 * Internal static properties
					 ****************************/

					protected static string $protectedStaticOne;

					private static string $privateStaticOne;

					protected static string $protectedStaticTwo;

					/****************
					 * Public methods
					 ****************/

					public function publicOne(): void {}

					public function publicTwo(): void {}

					public function publicThree(): void {}

					/***********************
					 * Public static methods
					 ***********************/

					public static function publicStaticOne(): void {}

					public static function publicStaticTwo(): void {}

					public static function publicStaticThree(): void {}

					/******************
					 * Internal methods
					 ******************/

					protected function protectedOne(): void {}

					private function privateOne(): void {}

					protected function protectedTwo(): void {}

					/*************************
					 * Internal static methods
					 *************************/

					protected static function protectedStaticOne(): void {}

					private static function privateStaticOne(): void {}

					protected static function protectedStaticTwo(): void {}
				}
				PHP,
			<<<'PHP'
				<?php

				enum Status
				{
					case Draft;

					case Published;

					case Archived;
				}

				class Foo
				{
					public const CONST_ONE = 1;

					public const CONST_TWO = 2;

					public const CONST_THREE = 3;

					public string $publicOne;

					public string $publicTwo;

					public string $publicThree;

					public static string $publicStaticOne;

					public static string $publicStaticTwo;

					public static string $publicStaticThree;

					protected string $protectedOne;

					private string $privateOne;

					protected string $protectedTwo;

					protected static string $protectedStaticOne;

					private static string $privateStaticOne;

					protected static string $protectedStaticTwo;

					public function publicOne(): void {}

					public function publicTwo(): void {}

					public function publicThree(): void {}

					public static function publicStaticOne(): void {}

					public static function publicStaticTwo(): void {}

					public static function publicStaticThree(): void {}

					protected function protectedOne(): void {}

					private function privateOne(): void {}

					protected function protectedTwo(): void {}

					protected static function protectedStaticOne(): void {}

					private static function privateStaticOne(): void {}

					protected static function protectedStaticTwo(): void {}
				}
				PHP,
		];

		yield 'section comments missing a space' => [
			<<<'PHP'
				<?php

				enum Status
				{
					/************
					 * Enum cases
					 ************/

					case Draft;

					case Published;

					case Archived;
				}

				class Foo
				{
					/*****************
					 * Class constants
					 *****************/

					public const CONST_ONE = 1;

					public const CONST_TWO = 2;

					public const CONST_THREE = 3;

					/*******************
					 * Public properties
					 *******************/

					public string $publicOne;

					public string $publicTwo;

					public string $publicThree;

					/**************************
					 * Public static properties
					 **************************/

					public static string $publicStaticOne;

					public static string $publicStaticTwo;

					public static string $publicStaticThree;

					/*********************
					 * Internal properties
					 *********************/

					protected string $protectedOne;

					private string $privateOne;

					protected string $protectedTwo;

					/****************************
					 * Internal static properties
					 ****************************/

					protected static string $protectedStaticOne;

					private static string $privateStaticOne;

					protected static string $protectedStaticTwo;

					/****************
					 * Public methods
					 ****************/

					public function publicOne(): void {}

					public function publicTwo(): void {}

					public function publicThree(): void {}

					/***********************
					 * Public static methods
					 ***********************/

					public static function publicStaticOne(): void {}

					public static function publicStaticTwo(): void {}

					public static function publicStaticThree(): void {}

					/******************
					 * Internal methods
					 ******************/

					protected function protectedOne(): void {}

					private function privateOne(): void {}

					protected function protectedTwo(): void {}

					/*************************
					 * Internal static methods
					 *************************/

					protected static function protectedStaticOne(): void {}

					private static function privateStaticOne(): void {}

					protected static function protectedStaticTwo(): void {}
				}
				PHP,
			<<<'PHP'
				<?php

				enum Status
				{
					/************
					 *Enum cases
					 ************/

					case Draft;

					case Published;

					case Archived;
				}

				class Foo
				{
					/*****************
					 *Class constants
					 *****************/

					public const CONST_ONE = 1;

					public const CONST_TWO = 2;

					public const CONST_THREE = 3;

					/*******************
					 *Public properties
					 *******************/

					public string $publicOne;

					public string $publicTwo;

					public string $publicThree;

					/**************************
					 *Public static properties
					 **************************/

					public static string $publicStaticOne;

					public static string $publicStaticTwo;

					public static string $publicStaticThree;

					/*********************
					 *Internal properties
					 *********************/

					protected string $protectedOne;

					private string $privateOne;

					protected string $protectedTwo;

					/****************************
					 *Internal static properties
					 ****************************/

					protected static string $protectedStaticOne;

					private static string $privateStaticOne;

					protected static string $protectedStaticTwo;

					/****************
					 *Public methods
					 ****************/

					public function publicOne(): void {}

					public function publicTwo(): void {}

					public function publicThree(): void {}

					/***********************
					 *Public static methods
					 ***********************/

					public static function publicStaticOne(): void {}

					public static function publicStaticTwo(): void {}

					public static function publicStaticThree(): void {}

					/******************
					 *Internal methods
					 ******************/

					protected function protectedOne(): void {}

					private function privateOne(): void {}

					protected function protectedTwo(): void {}

					/*************************
					 *Internal static methods
					 *************************/

					protected static function protectedStaticOne(): void {}

					private static function privateStaticOne(): void {}

					protected static function protectedStaticTwo(): void {}
				}
				PHP,
		];

		yield 'misplaced and duplicate section comments' => [
			<<<'PHP'
				<?php

				class Foo
				{
					/*******************
					 * Public properties
					 *******************/

					public string $publicOne;

					public string $publicTwo;

					/****************
					 * Public methods
					 ****************/

					public function publicOne(): void {}

					public function publicTwo(): void {}

					/******************
					 * Internal methods
					 ******************/

					protected function protectedOne(): void {}

					protected function protectedTwo(): void {}
				}
				PHP,
			<<<'PHP'
				<?php

				class Foo
				{
					/****************
					 * Public methods
					 ****************/
					/*******************
					 * Public properties
					 *******************/
					public string $publicOne;

					/*******************
					 * Public properties
					 *******************/
					public string $publicTwo;

					/******************
					 * Internal methods
					 ******************/
					public function publicOne(): void {}

					/****************
					 * Public methods
					 ****************/
					public function publicTwo(): void {}

					/******************
					 * Internal methods
					 ******************/
					protected function protectedOne(): void {}

					/******************
					 * Internal methods
					 ******************/
					protected function protectedTwo(): void {}
				}
				PHP,
		];

		yield 'completely wrong section comments' => [
			<<<'PHP'
				<?php

				class Foo
				{
					/*******************
					 * Public properties
					 *******************/

					public string $foo;

					/****************
					 * Public methods
					 ****************/

					public function foo(): void {}
				}
				PHP,
			<<<'PHP'
				<?php

				class Foo
				{
					/*************************
					 * Internal static methods
					 *************************/

					public string $foo;

					/****************************
					 * Internal static properties
					 ****************************/

					public function foo(): void {}
				}
				PHP,
		];

		yield 'normalizes whitespace around existing section comments' => [
			<<<'PHP'
				<?php

				class Foo
				{
					/*******************
					 * Public properties
					 *******************/

					public string $foo;

					/****************
					 * Public methods
					 ****************/

					public function bar(): void {}
				}
				PHP,
			<<<'PHP'
				<?php

				class Foo
				{




					/*******************
					 * Public properties
					 *******************/




					public string $foo;




					/****************
					 * Public methods
					 ****************/




					public function bar(): void {}
				}
				PHP,
		];

		yield 'does not add blank line before first section comment' => [
			<<<'PHP'
				<?php

				class Foo
				{
					/*******************
					 * Public properties
					 *******************/

					public string $foo;
				}
				PHP,
			<<<'PHP'
				<?php

				class Foo
				{
					/*******************
					 * Public properties
					 *******************/
					public string $foo;
				}
				PHP,
		];

		yield 'attributes remain attached to method' => [
			<<<'PHP'
				<?php

				class Foo
				{
					/****************
					 * Public methods
					 ****************/

					#[DataProvider('provideFixCases')]
					public function testFix(): void {}
				}
				PHP,
			<<<'PHP'
				<?php

				class Foo
				{
					#[DataProvider('provideFixCases')]
					public function testFix(): void {}
				}
				PHP,
		];

		yield 'multiple attributes remain attached to method' => [
			<<<'PHP'
				<?php

				class Foo
				{
					/****************
					 * Public methods
					 ****************/

					#[Test]
					#[DataProvider('provideFixCases')]
					public function testFix(): void {}
				}
				PHP,
			<<<'PHP'
				<?php

				class Foo
				{
					#[Test]
					#[DataProvider('provideFixCases')]
					public function testFix(): void {}
				}
				PHP,
		];

		yield 'adds whitespace to sections' => [
			<<<'PHP'
				<?php

				class Foo
				{
					/*******************
					 * Public properties
					 *******************/

					public string $a;

					/****************
					 * Public methods
					 ****************/

					public function b(): void {}
				}

				class Bar
				{
					/*******************
					 * Public properties
					 *******************/

					public string $c;

					/****************
					 * Public methods
					 ****************/

					public function d(): void {}
				}
				PHP,
			<<<'PHP'
				<?php

				class Foo
				{
					public string $a;
					public function b(): void {}
				}

				class Bar
				{
					public string $c;
					public function d(): void {}
				}
				PHP,
		];

		yield 'every class in a file gets its own sections' => [
			<<<'PHP'
				<?php

				class Foo
				{
					/*******************
					 * Public properties
					 *******************/

					public string $a;

					/****************
					 * Public methods
					 ****************/

					public function b(): void {}
				}

				class Bar
				{
					/*******************
					 * Public properties
					 *******************/

					public string $c;

					/****************
					 * Public methods
					 ****************/

					public function d(): void {}
				}
				PHP,
			<<<'PHP'
				<?php

				class Foo
				{
					public string $a;

					public function b(): void {}
				}

				class Bar
				{
					public string $c;

					public function d(): void {}
				}
				PHP,
		];

		yield 'sections in later classes are kept' => [
			<<<'PHP'
				<?php

				class Foo
				{
					/*******************
					 * Public properties
					 *******************/

					public string $a;
				}

				class Bar
				{
					/*******************
					 * Public properties
					 *******************/

					public string $b;
				}
				PHP,
			<<<'PHP'
				<?php

				class Foo
				{
					public string $a;
				}

				class Bar
				{
					/*******************
					 * Public properties
					 *******************/

					public string $b;
				}
				PHP,
		];

		yield 'code that is already correct is left alone' => [
			<<<'PHP'
				<?php

				class Foo
				{
					/*****************
					 * Class constants
					 *****************/

					public const A = 1;

					/*******************
					 * Public properties
					 *******************/

					#[Attr]
					public string $b; // trailing

					/****************
					 * Public methods
					 ****************/

					/**
					 * Docblock.
					 */
					public function c(): void {}
				}
				PHP,
		];

		yield 'enum with only methods has no enum cases section' => [
			<<<'PHP'
				<?php

				enum Suit
				{
					/****************
					 * Public methods
					 ****************/

					public function label(): string
					{
						return 'x';
					}
				}
				PHP,
			<<<'PHP'
				<?php

				enum Suit
				{
					public function label(): string
					{
						return 'x';
					}
				}
				PHP,
		];

		yield 'case in a switch is not an enum case' => [
			<<<'PHP'
				<?php

				enum Nothing
				{
					/****************
					 * Public methods
					 ****************/

					public function pick(int $x): int
					{
						switch ($x) {
							case 1:
								return 1;
							default:
								return 2;
						}
					}
				}
				PHP,
			<<<'PHP'
				<?php

				enum Nothing
				{
					public function pick(int $x): int
					{
						switch ($x) {
							case 1:
								return 1;
							default:
								return 2;
						}
					}
				}
				PHP,
		];

		yield 'promoted constructor parameters are not properties' => [
			<<<'PHP'
				<?php

				class Foo
				{
					/****************
					 * Public methods
					 ****************/

					public function __construct(
						private string $a,
						public int $b = 0,
					) {}
				}
				PHP,
			<<<'PHP'
				<?php

				class Foo
				{
					public function __construct(
						private string $a,
						public int $b = 0,
					) {}
				}
				PHP,
		];

		yield 'anonymous classes are left alone' => [
			<<<'PHP'
				<?php

				class Foo
				{
					/****************
					 * Public methods
					 ****************/

					public function bar(): object
					{
						return new class {
							public string $a;

							public function b(): void {}
						};
					}
				}
				PHP,
			<<<'PHP'
				<?php

				class Foo
				{
					public function bar(): object
					{
						return new class {
							public string $a;

							public function b(): void {}
						};
					}
				}
				PHP,
		];

		yield 'sections inside anonymous classes are not removed' => [
			<<<'PHP'
				<?php

				class Foo
				{
					/****************
					 * Public methods
					 ****************/

					public function bar(): object
					{
						return new class {
							/*******************
							 * Public properties
							 *******************/

							public string $a;
						};
					}
				}
				PHP,
			<<<'PHP'
				<?php

				class Foo
				{
					public function bar(): object
					{
						return new class {
							/*******************
							 * Public properties
							 *******************/

							public string $a;
						};
					}
				}
				PHP,
		];

		yield 'trailing comments stay with their statements' => [
			<<<'PHP'
				<?php

				class Foo
				{ // opening
					/*******************
					 * Public properties
					 *******************/

					public string $a; // trailing

					/****************
					 * Public methods
					 ****************/

					public function b(): void {}
				}
				PHP,
			<<<'PHP'
				<?php

				class Foo
				{ // opening
					public string $a; // trailing

					public function b(): void {}
				}
				PHP,
		];

		yield 'modifiers in the legacy order' => [
			<<<'PHP'
				<?php

				class Foo
				{
					/***********************
					 * Public static methods
					 ***********************/

					static public function bar(): void {}
				}
				PHP,
			<<<'PHP'
				<?php

				class Foo
				{
					static public function bar(): void {}
				}
				PHP,
		];

		yield 'elements without a visibility are public' => [
			<<<'PHP'
				<?php

				class Foo
				{
					/*******************
					 * Public properties
					 *******************/

					var $a;

					/****************
					 * Public methods
					 ****************/

					function b(): void {}
				}
				PHP,
			<<<'PHP'
				<?php

				class Foo
				{
					var $a;

					function b(): void {}
				}
				PHP,
		];

		yield 'several properties in one statement' => [
			<<<'PHP'
				<?php

				class Foo
				{
					/*******************
					 * Public properties
					 *******************/

					public string $a, $b;

					/*********************
					 * Internal properties
					 *********************/

					protected string $c;
				}
				PHP,
			<<<'PHP'
				<?php

				class Foo
				{
					public string $a, $b;

					protected string $c;
				}
				PHP,
		];

		yield 'trait imports do not get a section' => [
			<<<'PHP'
				<?php

				class Foo
				{
					use Bar;

					/*******************
					 * Public properties
					 *******************/

					public string $a;
				}
				PHP,
			<<<'PHP'
				<?php

				class Foo
				{
					use Bar;

					public string $a;
				}
				PHP,
		];

		yield 'indentation follows the code' => [
			<<<'PHP'
				<?php

				namespace Foo {
					class Bar
					{
						/*******************
						 * Public properties
						 *******************/

						public string $a;
					}
				}
				PHP,
			<<<'PHP'
				<?php

				namespace Foo {
					class Bar
					{
						public string $a;
					}
				}
				PHP,
		];
	}

	/******************
	 * Internal methods
	 ******************/

	protected function createFixer(): AbstractFixer
	{
		return new SectionCommentsFixer();
	}
}
