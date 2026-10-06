# Rule `Live627/global_native_namespace_import`

Fully qualifies references to PHP internal classes, interfaces, and traits and removes redundant import statements.

## Priority

This fixer has priority `0`. Higher priorities are executed first.

**Must run before:**

- `NoUnusedImportsFixer` — priority `-10`
- `OrderedImportsFixer` — priority `-30`
- `StatementIndentationFixer` — priority `-3`

**Must run after:**

- `NativeConstantInvocationFixer` — priority `1`
- `NativeFunctionInvocationFixer` — priority `1`
- `StringableForToStringFixer` — priority `37`

## Examples

### Example #1

```diff
--- Original
+++ Fixed
@@ @@
 
 namespace Foo;
 
-use DateTime;
-use DateTimeImmutable;
 use App\Bar;
 
-$d = new DateTime();
-$i = new DateTimeImmutable();
+$d = new \DateTime();
+$i = new \DateTimeImmutable();
 $b = new Bar();
```

### Example #2

```diff
--- Original
+++ Fixed
@@ @@
 
 use App\Model\User;
 use App\Service\UserManager;
-use Countable;
-use DateTime;
-use DateTimeImmutable;
-use Iterator;
-use IteratorAggregate;
-use RuntimeException;
-
 /**
  * This is an exceptional test classs.
  */
-class Test extends RuntimeException implements IteratorAggregate
+class Test extends \RuntimeException implements \IteratorAggregate
 {
 	/**
-	 * @var DateTime
+	 * @var \DateTime
 	 */
-	private DateTime $date;
+	private \DateTime $date;
 
 	/**
 	 * @var User
@@ @@
 	 */
 	private User $user;
 
-	private string $class = DateTime::class;
+	private string $class = \DateTime::class;
 
 	/**
-	 * @param DateTime|DateTimeImmutable $date
+	 * @param \DateTime|\DateTimeImmutable $date
 	 *
-	 * @return DateTime
+	 * @return \DateTime
 	 */
-	public function run(DateTime|DateTimeImmutable $date): DateTime
+	public function run(\DateTime|\DateTimeImmutable $date): \DateTime
 	{
-		if ($date instanceof DateTimeImmutable) {
-			throw new RuntimeException();
+		if ($date instanceof \DateTimeImmutable) {
+			throw new \RuntimeException();
 		}
 
-		DateTime::createFromFormat('Y-m-d', '2026-01-01');
+		\DateTime::createFromFormat('Y-m-d', '2026-01-01');
 
-		new DateTime();
-		new DateTimeImmutable();
+		new \DateTime();
+		new \DateTimeImmutable();
 
 		$manager = new UserManager();
 
-		return new DateTime();
+		return new \DateTime();
 	}
 
 	/**
-	 * @param Iterator&Countable $value
+	 * @param \Iterator&\Countable $value
 	 */
-	public function intersect(Iterator&Countable $value): void
+	public function intersect(\Iterator&\Countable $value): void
 	{
 	}
 
 	/**
-	 * @return Iterator<int, User>
+	 * @return \Iterator<int, User>
 	 */
-	public function getIterator(): Iterator
+	public function getIterator(): \Iterator
 	{
 		yield $this->user;
 	}
```

## Test Cases

These examples are taken from the fixer's PHPUnit test cases.

### new expression

```diff
--- Original
+++ Fixed
@@ @@
 
 namespace App;
 
-use DateTime;
-
 class Test
 {
 	public function run(): void
 	{
-		new DateTime();
+		new \DateTime();
 	}
 }
```

### extends

```diff
--- Original
+++ Fixed
@@ @@
 
 namespace App;
 
-use RuntimeException;
-
-class Test extends RuntimeException
+class Test extends \RuntimeException
 {
 }
```

### implements

```diff
--- Original
+++ Fixed
@@ @@
 
 namespace App;
 
-use IteratorAggregate;
-
-class Test implements IteratorAggregate
+class Test implements \IteratorAggregate
 {
 	public function getIterator(): \Traversable
 	{
```

### instanceof

```diff
--- Original
+++ Fixed
@@ @@
 
 namespace App;
 
-use DateTime;
-
 class Test
 {
 	public function run(object $value): bool
 	{
-		return $value instanceof DateTime;
+		return $value instanceof \DateTime;
 	}
 }
```

### catch

```diff
--- Original
+++ Fixed
@@ @@
 
 namespace App;
 
-use RuntimeException;
-
 class Test
 {
 	public function run(): void
 	{
 		try {
-		} catch (RuntimeException $e) {
+		} catch (\RuntimeException $e) {
 		}
 	}
 }
```

### parameter type

```diff
--- Original
+++ Fixed
@@ @@
 
 namespace App;
 
-use DateTime;
-
 class Test
 {
-	public function run(DateTime $date): void
+	public function run(\DateTime $date): void
 	{
 	}
 }
```

### return type

```diff
--- Original
+++ Fixed
@@ @@
 
 namespace App;
 
-use DateTime;
-
 class Test
 {
-	public function run(): DateTime
+	public function run(): \DateTime
 	{
-		return new DateTime();
+		return new \DateTime();
 	}
 }
```

### property type

```diff
--- Original
+++ Fixed
@@ @@
 
 namespace App;
 
-use DateTime;
-
 class Test
 {
-	private DateTime $date;
+	private \DateTime $date;
 }
```

### union type

```diff
--- Original
+++ Fixed
@@ @@
 
 namespace App;
 
-use DateTime;
-use DateTimeImmutable;
-
 class Test
 {
-	public function run(DateTime|DateTimeImmutable $date): void
+	public function run(\DateTime|\DateTimeImmutable $date): void
 	{
 	}
 }
```

### intersection type

```diff
--- Original
+++ Fixed
@@ @@
 
 namespace App;
 
-use Countable;
-use Iterator;
-
 class Test
 {
-	public function run(Iterator&Countable $value): void
+	public function run(\Iterator&\Countable $value): void
 	{
 	}
 }
```

### static call

```diff
--- Original
+++ Fixed
@@ @@
 
 namespace App;
 
-use DateTime;
-
 class Test
 {
 	public function run(): void
 	{
-		DateTime::createFromFormat('Y-m-d', '2026-01-01');
+		\DateTime::createFromFormat('Y-m-d', '2026-01-01');
 	}
 }
```

### class constant

```diff
--- Original
+++ Fixed
@@ @@
 
 namespace App;
 
-use DateTime;
-
 class Test
 {
-	private string $class = DateTime::class;
+	private string $class = \DateTime::class;
 }
```


## References

- Class: [`Live627\PhpCsFixer\CustomFixers\GlobalNativeNamespaceImportFixer`](../src\GlobalNativeNamespaceImportFixer.php)
  - `src\GlobalNativeNamespaceImportFixer.php`
- Test: [`GlobalNativeNamespaceImportFixerTest`](../tests/GlobalNativeNamespaceImportFixerTest.php)
  - `tests/GlobalNativeNamespaceImportFixerTest.php`
