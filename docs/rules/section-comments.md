# Rule `section_comments`

Inserts sectioning comments. This is meant to be used in combination with the `ordered_class_elements` rule.

## Examples

### Example #1

```diff
--- Original
+++ Fixed
@@ @@
 #Warning: Strings contain different line endings!
 <?php
 
 class Foo
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
 
+	/*************************
+	 * Internal static methods
+	 *************************/
+
 	/**
 	 *
 	 */
```
