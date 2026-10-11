# Rule `Live627/test_method_docblock`

Adds missing docblocks, summaries and `Expected:` sections to public test methods and aligns them with the method declaration.

## Priority

This fixer has priority `0`. Higher priorities are executed first.

**Must run after:**

- `PhpUnitTestAnnotationFixer` — priority `10`

## Examples

### Example #1

```diff
--- Original
+++ Fixed
@@ @@
 
 class FooTest extends TestCase
 {
-	public function testSomethingWorks(): void
+    /**
+     * Something works.
+     *
+     * Expected: TODO describe the call and the assertion result.
+     */
+    public function testSomethingWorks(): void
 	{
 		$this->assertTrue(true);
 	}
 
-	/**
-		* Already documented.
-	  */
-	#[DataProvider('provideValues')]
+    /**
+     * Already documented.
+     *
+     * Expected: TODO describe the call and the assertion result.
+     */
+    #[DataProvider('provideValues')]
 	public function testValues(int $value): void
 	{
 		$this->assertGreaterThan(0, $value);
```

### Example #2

```diff
--- Original
+++ Fixed
@@ @@
 
 class FooTest extends TestCase
 {
-	/**
-	 * Does something.
-	 *
-	 * @dataProvider provideValues
-	 */
-	public function testSomething(int $value): void
+    /**
+     * Does something.
+     *
+     * Expected: TODO describe the call and the assertion result.
+     *
+     * @dataProvider provideValues
+     */
+    public function testSomething(int $value): void
 	{
 	}
 
-	/**
-	 * @dataProvider provideOther
-	 */
-	public function testOther(int $value): void
+    /**
+     * Other.
+     *
+     * Expected: TODO describe the call and the assertion result.
+     *
+     * @dataProvider provideOther
+     */
+    public function testOther(int $value): void
 	{
 	}
 }
```

## Test Cases

These examples are taken from the fixer's PHPUnit test cases.


## Advanced Examples

These examples demonstrate how this fixer interacts with other fixers in an integration test.

### Integration example # 3

**Ruleset:**

```php
[
    'Live627/test_method_docblock' => true,
    'php_unit_test_annotation' => [
        'style' => 'prefix',
    ],
]
```

```diff
--- Original
+++ Fixed
@@ @@
 
 class FooTest extends TestCase
 {
+	/**
+	 * Something works.
+	 *
+	 * Expected: TODO describe the call and the assertion result.
+	 */
 	public function testSomethingWorks(): void
 	{
 	}
@@ @@
 	 * Other thing.
 	 *
 	 * Expected: Passes.
-	 * @test
+	 *
 	 */
-	public function otherThing(): void
+	public function testOtherThing(): void
 	{
 	}
 
 	/**
-	 * @test
+	 * Yabba dabba doo.
+	 *
+	 * Expected: TODO describe the call and the assertion result.
 	 */
-	public function yabba_dabba_doo(): void
+	public function testYabba_dabba_doo(): void
 	{
 	}
 }
\ No newline at end of file
```


## References

- Class: [`Live627\PhpCsFixer\CustomFixers\TestMethodDocblockFixer`](../src\TestMethodDocblockFixer.php)
  - `src\TestMethodDocblockFixer.php`
- Test: [`TestMethodDocblockFixerTest`](../tests/TestMethodDocblockFixerTest.php)
  - `tests/TestMethodDocblockFixerTest.php`
