# Rule `Live627/array_key_exists_to_isset`

Replaces array_key_exists() with isset().

## Priority

This fixer has priority `0`. Higher priorities are executed first.

## Warning

This rule is risky.

Changes behavior when the array element exists and contains null.  Be sure to have unit tests that cover every possible permutation!.

## Examples

### Example #1

```diff
--- Original
+++ Fixed
@@ @@
 <?php
-array_key_exists('foo', $array);
+isset($array['foo']);
```

## Test Cases

These examples are taken from the fixer's PHPUnit test cases.

### simple string key

```diff
--- Original
+++ Fixed
@@ @@
 <?php
 
-array_key_exists('foo', $array);
+isset($array['foo']);
```

### variable key

```diff
--- Original
+++ Fixed
@@ @@
 <?php
 
-array_key_exists($key, $data);
+isset($data[$key]);
```

### inside if

```diff
--- Original
+++ Fixed
@@ @@
 <?php
 
-if (array_key_exists($key, $data)) {
+if (isset($data[$key])) {
 	doSomething();
 }
```

### array access expression

```diff
--- Original
+++ Fixed
@@ @@
 <?php
 
-array_key_exists('name', $items[$i]);
+isset($items[$i]['name']);
```

### function call in key expression

```diff
--- Original
+++ Fixed
@@ @@
 <?php
 
-array_key_exists(foo($a, $b), $data);
+isset($data[foo($a, $b)]);
```

### function call in array expression

```diff
--- Original
+++ Fixed
@@ @@
 <?php
 
-array_key_exists('foo', getData($a, $b));
+isset(getData($a, $b)['foo']);
```


## References

- Class: [`Live627\PhpCsFixer\CustomFixers\ArrayKeyExistsToIssetFixer`](../src\ArrayKeyExistsToIssetFixer.php)
  - `src\ArrayKeyExistsToIssetFixer.php`
- Test: [`ArrayKeyExistsToIssetFixerTest`](../tests/ArrayKeyExistsToIssetFixerTest.php)
  - `tests/ArrayKeyExistsToIssetFixerTest.php`
