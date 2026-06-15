# Rule `Live627/snake_case_identifiers`

Converts camelCase identifiers to snake_case.

## Warning

This rule is risky.

Risky when variables are referenced dynamically through functions such as compact(), extract(), or parse_str().

## Configuration

### `exclude`

Names to exclude.

**Default:**

```php
array (
)
```

### `exclude_patterns`

Patterns to exclude.

**Default:**

```php
array (
)
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
array (
  'exclude' => 
  array (
    0 => '$memberName',
  ),
)
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
array (
  'exclude_patterns' => 
  array (
    0 => '/^\\$txt[A-Z]/',
  ),
)
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

## References

- Class: [`Live627\PhpCsFixer\CustomFixers\SnakeCaseIdentifiersFixer`](../src\SnakeCaseIdentifiersFixer.php)
  - `src\SnakeCaseIdentifiersFixer.php`
- Test: [`SnakeCaseIdentifiersFixerTest`](../tests/SnakeCaseIdentifiersFixerTest.php)
  - `tests/SnakeCaseIdentifiersFixerTest.php`
