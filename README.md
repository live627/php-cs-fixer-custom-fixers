# Live627 PHP-CS-Fixer Rules
A set of custom fixers for PHP-CS-Fixer 

## Installation

Install via Composer:

```bash
composer require --dev live627/php-cs-fixer-custom-fixers
```

### Configuration

Add the custom fixers to your `.php-cs-fixer.dist.php` configuration file:

```php
<?php

$finder = PhpCsFixer\Finder::create()
    ->in(__DIR__);

$config = new PhpCsFixer\Config();
return $config
    ->setFinder($finder)
    ->registerCustomFixers([
        new Live627\PhpCsFixer\CustomFixers\ArrayKeyExistsToIssetFixer(),
        new Live627\PhpCsFixer\CustomFixers\GlobalNativeNamespaceImportFixer(),
        new Live627\PhpCsFixer\CustomFixers\SnakeCaseIdentifiersFixer(),
        new Live627\PhpCsFixer\CustomFixers\SectionCommentsFixer(),
    ])
    ->setRules([
        'Live627/array_key_exists_to_isset' => true,
        'Live627/global_native_namespace_import' => true,
        'Live627/snake_case_identifiers' => true,
        'SMF/section_comments' => true,
    ]);
```

Then run PHP-CS-Fixer:

```bash
vendor/bin/php-cs-fixer fix
```

<!-- BEGIN AUTO-GENERATED RULES -->

## Available Rules

- [`Live627/array_key_exists_to_isset`](docs/rules/array-key-exists-to-isset.md) (`Live627\PhpCsFixer\CustomFixers\ArrayKeyExistsToIssetFixer`)
   - Replaces array_key_exists() with isset().

- [`Live627/global_native_namespace_import`](docs/rules/global-native-namespace-import.md) (`Live627\PhpCsFixer\CustomFixers\GlobalNativeNamespaceImportFixer`)
   - Fully qualifies references to PHP internal classes, interfaces, and traits and removes redundant import statements.

- [`Live627/snake_case_identifiers`](docs/rules/snake-case-identifiers.md) (`Live627\PhpCsFixer\CustomFixers\SnakeCaseIdentifiersFixer`)
   - Converts camelCase identifiers to snake_case.

- [`SMF/section_comments`](docs/rules/section-comments.md) (`Live627\PhpCsFixer\CustomFixers\SectionCommentsFixer`)
   - Inserts sectioning comments. This is meant to be used in combination with the `ordered_class_elements` rule.

<!-- END AUTO-GENERATED RULES -->

More documentation here...