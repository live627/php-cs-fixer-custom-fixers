# Rule `SMF/section_comments`

Inserts sectioning comments. This is meant to be used in combination with the `ordered_class_elements` rule.

## Priority

This fixer has priority `54`. Higher priorities are executed first.

**Must run after:**

- `OrderedClassElementsFixer` — priority `65`

## Examples

### Example #1

```diff
--- Original
+++ Fixed
@@ @@
 
 class Foo
 {
+	/*****************
+	 * Class constants
+	 *****************/
+
 	/**
 	 *
 	 */
 	const MY_CONSTANT = 1;
 
+	/*******************
+	 * Public properties
+	 *******************/
+
 	/**
 	 *
 	 */
 	public string $a = '';
 
+	/*********************
+	 * Internal properties
+	 *********************/
+
 	/**
 	 *
 	 */
@@ @@
 	 */
 	private string $c = '';
 
+	/**************************
+	 * Public static properties
+	 **************************/
+
 	/**
 	 *
 	 */
 	public static string $d = '';
 
+	/****************************
+	 * Internal static properties
+	 ****************************/
+
 	/**
 	 *
 	 */
@@ @@
 	 */
 	private static string $f = '';
 
+	/****************
+	 * Public methods
+	 ****************/
+
 	/**
 	 *
 	 */
 	public function method1(): void {}
 
+	/******************
+	 * Internal methods
+	 ******************/
+
 	/**
 	 *
 	 */
@@ @@
 	 */
 	private function method3(): void {}
 
+	/***********************
+	 * Public static methods
+	 ***********************/
+
 	/**
 	 *
 	 */
 	public static function method4(): void {}
+
+	/*************************
+	 * Internal static methods
+	 *************************/
 
 	/**
 	 *
```

## Test Cases

These examples are taken from the fixer's PHPUnit test cases.

### misplaced and duplicate section comments

```diff
--- Original
+++ Fixed
@@ @@
 
 class Foo
 {
-	/****************
-	 * Public methods
-	 ****************/
 	/*******************
 	 * Public properties
 	 *******************/
+
 	public string $publicOne;
 
-	/*******************
-	 * Public properties
-	 *******************/
 	public string $publicTwo;
 
-	/******************
-	 * Internal methods
-	 ******************/
-	public function publicOne(): void {}
-
 	/****************
 	 * Public methods
 	 ****************/
+
+	public function publicOne(): void {}
+
 	public function publicTwo(): void {}
 
 	/******************
 	 * Internal methods
 	 ******************/
+
 	protected function protectedOne(): void {}
 
-	/******************
-	 * Internal methods
-	 ******************/
 	protected function protectedTwo(): void {}
 }
```

### completely wrong section comments

```diff
--- Original
+++ Fixed
@@ @@
 
 class Foo
 {
-	/*************************
-	 * Internal static methods
-	 *************************/
+	/*******************
+	 * Public properties
+	 *******************/
 
 	public string $foo;
 
-	/****************************
-	 * Internal static properties
-	 ****************************/
+	/****************
+	 * Public methods
+	 ****************/
 
 	public function foo(): void {}
 }
```

### normalizes whitespace around existing section comments

```diff
--- Original
+++ Fixed
@@ @@
 
 class Foo
 {
-
-
-
-
 	/*******************
 	 * Public properties
 	 *******************/
 
-
-
-
 	public string $foo;
 
-
-
-
 	/****************
 	 * Public methods
 	 ****************/
-
-
-
 
 	public function bar(): void {}
 }
```

### does not add blank line before first section comment

```diff
--- Original
+++ Fixed
@@ @@
 	/*******************
 	 * Public properties
 	 *******************/
+
 	public string $foo;
 }
```

### attributes remain attached to method

```diff
--- Original
+++ Fixed
@@ @@
 
 class Foo
 {
+	/****************
+	 * Public methods
+	 ****************/
+
 	#[DataProvider('provideFixCases')]
 	public function testFix(): void {}
 }
```


## Advanced Examples

These examples demonstrate how this fixer interacts with other fixers in an integration test.

### Integration example # 1

**Ruleset:**

```php
[
    'ordered_class_elements' => [
        'order' => [
            'use_trait',
            'case',
            'constant_public',
            'constant_protected',
            'constant_private',
            'property_public',
            'property_public_static',
            'property_protected',
            'property_private',
            'property_protected_static',
            'property_private_static',
            'method_public',
            'method_public_static',
            'method_protected',
            'method_private',
            'method_protected_static',
            'method_private_static',
        ],
        'sort_algorithm' => 'none',
    ],
    'SMF/section_comments' => true,
]
```

```diff
--- Original
+++ Fixed
@@ @@
 
 class Foo
 {
-	protected static string $protectedStaticOne;
+	/*****************
+	 * Class constants
+	 *****************/
 
-	protected static string $protectedStaticTwo;
+	public const CONST_ONE = 1;
 
-	public function publicOne(): void {}
+	const CONST_TWO = 2;
 
-	public function publicTwo(): void {}
+	private const CONST_THREE = 3;
 
-	public function publicThree(): void {}
+	/*******************
+	 * Public properties
+	 *******************/
 
-	public const CONST_ONE = 1;
+	public string $publicOne;
 
-	const CONST_TWO = 2;
+	public string $publicTwo;
 
-	private const CONST_THREE = 3;
+	public string $publicThree;
 
-	private function privateOne(): void {}
+	/**************************
+	 * Public static properties
+	 **************************/
 
 	public static string $publicStaticOne;
 
@@ @@
 
 	public static string $publicStaticThree;
 
-	protected function protectedOne(): void {}
+	/*********************
+	 * Internal properties
+	 *********************/
 
-	protected function protectedTwo(): void {}
+	protected string $protectedOne;
 
-	public string $publicOne;
+	protected string $protectedTwo;
 
-	public string $publicTwo;
+	private string $privateOne;
 
-	public string $publicThree;
+	/****************************
+	 * Internal static properties
+	 ****************************/
 
-	protected static function protectedStaticOne(): void {}
+	protected static string $protectedStaticOne;
 
-	protected static function protectedStaticTwo(): void {}
+	protected static string $protectedStaticTwo;
 
-	private static function privateStaticOne(): void {}
+	private static string $privateStaticOne;
 
-	private static string $privateStaticOne;
+	/****************
+	 * Public methods
+	 ****************/
 
+	public function publicOne(): void {}
+
+	public function publicTwo(): void {}
+
+	public function publicThree(): void {}
+
+	/***********************
+	 * Public static methods
+	 ***********************/
+
 	public static function publicStaticOne(): void {}
 
 	public static function publicStaticTwo(): void {}
@@ @@
 
 	public static function publicStaticThree(): void {}
 
-	protected string $protectedOne;
+	/******************
+	 * Internal methods
+	 ******************/
 
-	protected string $protectedTwo;
+	protected function protectedOne(): void {}
 
-	private string $privateOne;
+	protected function protectedTwo(): void {}
+
+	private function privateOne(): void {}
+
+	/*************************
+	 * Internal static methods
+	 *************************/
+
+	protected static function protectedStaticOne(): void {}
+
+	protected static function protectedStaticTwo(): void {}
+
+	private static function privateStaticOne(): void {}
 }
\ No newline at end of file
```


## References

- Class: [`Live627\PhpCsFixer\CustomFixers\SectionCommentsFixer`](../src\SectionCommentsFixer.php)
  - `src\SectionCommentsFixer.php`
- Test: [`SectionCommentsFixerTest`](../tests/SectionCommentsFixerTest.php)
  - `tests/SectionCommentsFixerTest.php`
