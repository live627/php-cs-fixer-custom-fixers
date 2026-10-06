# Rule `Live627/snake_case_identifiers`

Converts camelCase identifiers to snake_case.

## Priority

This fixer has priority `0`. Higher priorities are executed first.

## Warning

This rule is risky.

Risky when variables are referenced dynamically through functions such as compact(), extract(), or parse_str().

## Configuration

### `exclude`

Names to exclude.

**Default:**

```php
[] // empty array
```

### `exclude_patterns`

Patterns to exclude.

**Default:**

```php
[] // empty array
```

## Examples

### Example #1

```diff
--- Original
+++ Fixed
@@ @@
 <?php
 
-$memberName = 1;
-$txtBirthday = 1;
+$member_name = 1;
+$txt_birthday = 1;
```

### Example #2

**Configuration**

```php
[
    'exclude' => [
        '$memberName',
    ],
]
```

```diff
--- Original
+++ Fixed
@@ @@
 <?php
 
 $memberName = 1;
-$txtBirthday = 1;
+$txt_birthday = 1;
```

### Example #3

**Configuration**

```php
[
    'exclude_patterns' => [
        '/^\\$txt[A-Z]/',
    ],
]
```

```diff
--- Original
+++ Fixed
@@ @@
 <?php
 
-$memberName = 1;
+$member_name = 1;
 $txtBirthday = 1;
```

## Test Cases

These examples are taken from the fixer's PHPUnit test cases.

### phpdoc rename

```diff
--- Original
+++ Fixed
@@ @@
 <?php
 /**
- * @param string $memberName
+ * @param string $member_name
  */
-function test($memberName) {}
+function test($member_name) {}
```

### multiple variables

```diff
--- Original
+++ Fixed
@@ @@
-<?php $memberName = $userId + $displayName;
+<?php $member_name = $user_id + $display_name;
```

### phpdoc and variables

```diff
--- Original
+++ Fixed
@@ @@
 <?php
 
 /**
- * @param string $memberName
+ * @param string $member_name
  */
-function test($memberName)
+function test($member_name)
 {
-	$currentUser = $memberName;
+	$current_user = $member_name;
 }
```


## References

- Class: [`Live627\PhpCsFixer\CustomFixers\SnakeCaseIdentifiersFixer`](../src\SnakeCaseIdentifiersFixer.php)
  - `src\SnakeCaseIdentifiersFixer.php`
- Test: [`SnakeCaseIdentifiersFixerTest`](../tests/SnakeCaseIdentifiersFixerTest.php)
  - `tests/SnakeCaseIdentifiersFixerTest.php`
