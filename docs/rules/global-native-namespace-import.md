# Rule `Live627/global_native_namespace_import`

Fully qualifies references to PHP internal classes, interfaces, and traits and removes redundant import statements.

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

## References

- Class: [`Live627\PhpCsFixer\CustomFixers\GlobalNativeNamespaceImportFixer`](../src\GlobalNativeNamespaceImportFixer.php)
  - `src\GlobalNativeNamespaceImportFixer.php`
- Test: [`GlobalNativeNamespaceImportFixerTest`](../tests/GlobalNativeNamespaceImportFixerTest.php)
  - `tests/GlobalNativeNamespaceImportFixerTest.php`
