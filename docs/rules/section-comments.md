# Rule `SMF/section_comments`

Inserts sectioning comments. This is meant to be used in combination with the `ordered_class_elements` rule.

## Priority

This fixer has priority `54`. Higher priorities are executed first.

**Must run before:**

- `IndentationTypeFixer` — priority `50`
- `NoExtraBlankLinesFixer` — priority `-20`

**Must run after:**

- `ClassAttributesSeparationFixer` — priority `55`
- `OrderedClassElementsFixer` — priority `65`

## Examples

### Example #1

```diff
--- Original
+++ Fixed
@@ @@
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

## References

- Class: [`Live627\PhpCsFixer\CustomFixers\SectionCommentsFixer`](../src\SectionCommentsFixer.php)
  - `src\SectionCommentsFixer.php`
- Test: [`SectionCommentsFixerTest`](../tests/SectionCommentsFixerTest.php)
  - `tests/SectionCommentsFixerTest.php`
