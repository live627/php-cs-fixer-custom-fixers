# Rule `array_key_exists_to_isset`

Replaces array_key_exists() with isset().

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
