# Rule `Live627/phpdoc_tag_continuation_indent`

Continuation lines of PHPDoc tags must use the configured indentation.

## Priority

This fixer has priority `-43`. Higher priorities are executed first.

**Must run after:**

- `PhpdocAlignFixer` — priority `-42`

## Configuration

### `indent`

Number of spaces following the standard PHPDoc prefix (` * `) on continuation lines.

**Default:**

```php
3
```

## Examples

### Example #1

```diff
--- Original
+++ Fixed
@@ @@
 
 /**
  * @param string $name Description.
- * Continuation of the description.
- *   Another continuation.
+ *    Continuation of the description.
+ *      Another continuation.
  */
```

### Example #2

**Configuration**

```php
[
    'indent' => 4,
]
```

```diff
--- Original
+++ Fixed
@@ @@
 
 /**
  * @param string $name Description.
- * Continuation of the description.
- *   Another continuation.
+ *     Continuation of the description.
+ *       Another continuation.
  */
```

## Test Cases

These examples are taken from the fixer's PHPUnit test cases.


## Advanced Examples

These examples demonstrate how this fixer interacts with other fixers in an integration test.

### Integration example # 1

**Ruleset:**

```php
[
    'phpdoc_align' => true,
    'Live627/phpdoc_tag_continuation_indent' => [
        'indent' => 3,
    ],
]
```

```diff
--- Original
+++ Fixed
@@ @@
 	 * Mauris massa. Vestibulum lacinia arcu eget nulla. Class aptent taciti sociosqu
 	 * ad litora torquent per conubia nostra, per inceptos himenaeos.
 	 *
-	 * @param string $name The name of the item.
-	 *        Additional information about the name.
-	 *        Further details about its expected format.
-	 * @param int $count Number of items.
-	 *        Additional information about the count.
+	 * @param string $name  The name of the item.
+	 *    Additional information about the name.
+	 *    Further details about its expected format.
+	 * @param int    $count Number of items.
+	 *    Additional information about the count.
 	 */
 	public function doSomething(string $name, int $count): void
 	{
 	}
 }
\ No newline at end of file
```


## References

- Class: [`Live627\PhpCsFixer\CustomFixers\PhpdocTagContinuationIndentFixer`](../src\PhpdocTagContinuationIndentFixer.php)
  - `src\PhpdocTagContinuationIndentFixer.php`
- Test: [`PhpdocTagContinuationIndentFixerTest`](../tests/PhpdocTagContinuationIndentFixerTest.php)
  - `tests/PhpdocTagContinuationIndentFixerTest.php`
