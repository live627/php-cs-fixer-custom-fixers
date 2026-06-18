<?php

declare(strict_types=1);

use Live627\PhpCsFixer\CustomFixers\GlobalNativeNamespaceImportFixer;
use PhpCsFixer\AbstractFixer;
use PhpCsFixer\Tests\Test\AbstractFixerTestCase;
use PHPUnit\Framework\Attributes\DataProvider;

final class GlobalNativeNamespaceImportFixerTest extends AbstractFixerTestCase
{
	#[DataProvider('provideFixCases')]
	/****************
	 * Public methods
	 ****************/

	public function testFix(string $expected, ?string $input = null): void
	{
		$this->doTest($expected, $input);
	}

	/***********************
	 * Public static methods
	 ***********************/

	public static function provideFixCases(): iterable
	{
yield 'new expression' => [
	<<<'PHP'
		<?php

		namespace App;

		class Test
		{
			public function run(): void
			{
				new \DateTime();
			}
		}
		PHP,
	<<<'PHP'
		<?php

		namespace App;

		use DateTime;

		class Test
		{
			public function run(): void
			{
				new DateTime();
			}
		}
		PHP,
];

yield 'extends' => [
	<<<'PHP'
		<?php

		namespace App;

		class Test extends \RuntimeException
		{
		}
		PHP,
	<<<'PHP'
		<?php

		namespace App;

		use RuntimeException;

		class Test extends RuntimeException
		{
		}
		PHP,
];

yield 'implements' => [
	<<<'PHP'
		<?php

		namespace App;

		class Test implements \IteratorAggregate
		{
			public function getIterator(): \Traversable
			{
				yield 1;
			}
		}
		PHP,
	<<<'PHP'
		<?php

		namespace App;

		use IteratorAggregate;

		class Test implements IteratorAggregate
		{
			public function getIterator(): \Traversable
			{
				yield 1;
			}
		}
		PHP,
];

yield 'instanceof' => [
	<<<'PHP'
		<?php

		namespace App;

		class Test
		{
			public function run(object $value): bool
			{
				return $value instanceof \DateTime;
			}
		}
		PHP,
	<<<'PHP'
		<?php

		namespace App;

		use DateTime;

		class Test
		{
			public function run(object $value): bool
			{
				return $value instanceof DateTime;
			}
		}
		PHP,
];

yield 'catch' => [
	<<<'PHP'
		<?php

		namespace App;

		class Test
		{
			public function run(): void
			{
				try {
				} catch (\RuntimeException $e) {
				}
			}
		}
		PHP,
	<<<'PHP'
		<?php

		namespace App;

		use RuntimeException;

		class Test
		{
			public function run(): void
			{
				try {
				} catch (RuntimeException $e) {
				}
			}
		}
		PHP,
];

yield 'parameter type' => [
	<<<'PHP'
		<?php

		namespace App;

		class Test
		{
			public function run(\DateTime $date): void
			{
			}
		}
		PHP,
	<<<'PHP'
		<?php

		namespace App;

		use DateTime;

		class Test
		{
			public function run(DateTime $date): void
			{
			}
		}
		PHP,
];

yield 'return type' => [
	<<<'PHP'
		<?php

		namespace App;

		class Test
		{
			public function run(): \DateTime
			{
				return new \DateTime();
			}
		}
		PHP,
	<<<'PHP'
		<?php

		namespace App;

		use DateTime;

		class Test
		{
			public function run(): DateTime
			{
				return new DateTime();
			}
		}
		PHP,
];

yield 'property type' => [
	<<<'PHP'
		<?php

		namespace App;

		class Test
		{
			private \DateTime $date;
		}
		PHP,
	<<<'PHP'
		<?php

		namespace App;

		use DateTime;

		class Test
		{
			private DateTime $date;
		}
		PHP,
];

yield 'union type' => [
	<<<'PHP'
		<?php

		namespace App;

		class Test
		{
			public function run(\DateTime|\DateTimeImmutable $date): void
			{
			}
		}
		PHP,
	<<<'PHP'
		<?php

		namespace App;

		use DateTime;
		use DateTimeImmutable;

		class Test
		{
			public function run(DateTime|DateTimeImmutable $date): void
			{
			}
		}
		PHP,
];

yield 'intersection type' => [
	<<<'PHP'
		<?php

		namespace App;

		class Test
		{
			public function run(\Iterator&\Countable $value): void
			{
			}
		}
		PHP,
	<<<'PHP'
		<?php

		namespace App;

		use Countable;
		use Iterator;

		class Test
		{
			public function run(Iterator&Countable $value): void
			{
			}
		}
		PHP,
];

yield 'static call' => [
	<<<'PHP'
		<?php

		namespace App;

		class Test
		{
			public function run(): void
			{
				\DateTime::createFromFormat('Y-m-d', '2026-01-01');
			}
		}
		PHP,
	<<<'PHP'
		<?php

		namespace App;

		use DateTime;

		class Test
		{
			public function run(): void
			{
				DateTime::createFromFormat('Y-m-d', '2026-01-01');
			}
		}
		PHP,
];

yield 'class constant' => [
	<<<'PHP'
		<?php

		namespace App;

		class Test
		{
			private string $class = \DateTime::class;
		}
		PHP,
	<<<'PHP'
		<?php

		namespace App;

		use DateTime;

		class Test
		{
			private string $class = DateTime::class;
		}
		PHP,
];

yield 'many properties' => [
	<<<'PHP'
		<?php

		namespace App;

		class Example
		{
		    public function test(): void
		    {
		        $date = new \DateTime();
		        $immutable = new \DateTimeImmutable();

		        $reflection = new \ReflectionClass(self::class);

		        $exception = new \RuntimeException();

		        $list = new \ArrayObject();

		        $iterator = new \ArrayIterator([]);

		        $storage = new \SplObjectStorage();

		        $doc = new \DOMDocument();

		        $xml = new \SimpleXMLElement('<root />');

		        $callback = new \Closure(static fn () => null);

		        $generator = (static function (): \Generator {
		            yield 1;
		        })();

		        $value = \PHP_INT_MAX;
		    }
		}
		PHP,
	<<<'PHP'
		<?php

		namespace App;

		use ArrayIterator;
		use ArrayObject;
		use Closure;
		use DateTime;
		use DateTimeImmutable;
		use DOMDocument;
		use Generator;
		use ReflectionClass;
		use RuntimeException;
		use SimpleXMLElement;
		use SplObjectStorage;

		class Example
		{
		    public function test(): void
		    {
		        $date = new DateTime();
		        $immutable = new DateTimeImmutable();

		        $reflection = new ReflectionClass(self::class);

		        $exception = new RuntimeException();

		        $list = new ArrayObject();

		        $iterator = new ArrayIterator([]);

		        $storage = new SplObjectStorage();

		        $doc = new DOMDocument();

		        $xml = new SimpleXMLElement('<root />');

		        $callback = new Closure(static fn () => null);

		        $generator = (static function (): Generator {
		            yield 1;
		        })();

		        $value = \PHP_INT_MAX;
		    }
		}
		PHP,
];

yield 'all replacement targets' => [
	<<<'PHP'
		<?php

		namespace App;

		class Test extends \RuntimeException implements \IteratorAggregate
		{
			private \DateTime $date;

			private string $class = \DateTime::class;

			public function run(\DateTime|\DateTimeImmutable $date): \DateTime
			{
				if ($date instanceof \DateTimeImmutable) {
					throw new \RuntimeException();
				}

				\DateTime::createFromFormat('Y-m-d', '2026-01-01');

				new \DateTime();
				new \DateTimeImmutable();

				return new \DateTime();
			}

			public function intersect(\Iterator&\Countable $value): void
			{
			}

			public function getIterator(): \Iterator
			{
				yield 1;
			}
		}
		PHP,
	<<<'PHP'
		<?php

		namespace App;

		use Countable;
		use DateTime;
		use DateTimeImmutable;
		use Iterator;
		use IteratorAggregate;
		use RuntimeException;

		class Test extends RuntimeException implements IteratorAggregate
		{
			private DateTime $date;

			private string $class = DateTime::class;

			public function run(DateTime|DateTimeImmutable $date): DateTime
			{
				if ($date instanceof DateTimeImmutable) {
					throw new RuntimeException();
				}

				DateTime::createFromFormat('Y-m-d', '2026-01-01');

				new DateTime();
				new DateTimeImmutable();

				return new DateTime();
			}

			public function intersect(Iterator&Countable $value): void
			{
			}

			public function getIterator(): Iterator
			{
				yield 1;
			}
		}
		PHP,
];

yield 'all replacement targets with docblocks' => [
	<<<'PHP'
		<?php

		namespace App;

		use App\Model\User;
		use App\Service\UserManager;
		/**
		 * This is an exceptional test classs.
		 */
		class Test extends \RuntimeException implements \IteratorAggregate
		{
			/**
			 * @var \DateTime
			 */
			private \DateTime $date;

			/**
			 * @var User
			 */
			private User $user;

			private string $class = \DateTime::class;

			/**
			 * @param \DateTime|\DateTimeImmutable $date
			 *
			 * @return \DateTime
			 */
			public function run(\DateTime|\DateTimeImmutable $date): \DateTime
			{
				if ($date instanceof \DateTimeImmutable) {
					throw new \RuntimeException();
				}

				\DateTime::createFromFormat('Y-m-d', '2026-01-01');

				new \DateTime();
				new \DateTimeImmutable();

				$manager = new UserManager();

				return new \DateTime();
			}

			/**
			 * @param \Iterator&\Countable $value
			 */
			public function intersect(\Iterator&\Countable $value): void
			{
			}

			/**
			 * @return \Iterator<int, User>
			 */
			public function getIterator(): \Iterator
			{
				yield $this->user;
			}
		}
		PHP,
	<<<'PHP'
		<?php

		namespace App;

		use App\Model\User;
		use App\Service\UserManager;
		use Countable;
		use DateTime;
		use DateTimeImmutable;
		use Iterator;
		use IteratorAggregate;
		use RuntimeException;

		/**
		 * This is an exceptional test classs.
		 */
		class Test extends RuntimeException implements IteratorAggregate
		{
			/**
			 * @var DateTime
			 */
			private DateTime $date;

			/**
			 * @var User
			 */
			private User $user;

			private string $class = DateTime::class;

			/**
			 * @param DateTime|DateTimeImmutable $date
			 *
			 * @return DateTime
			 */
			public function run(DateTime|DateTimeImmutable $date): DateTime
			{
				if ($date instanceof DateTimeImmutable) {
					throw new RuntimeException();
				}

				DateTime::createFromFormat('Y-m-d', '2026-01-01');

				new DateTime();
				new DateTimeImmutable();

				$manager = new UserManager();

				return new DateTime();
			}

			/**
			 * @param Iterator&Countable $value
			 */
			public function intersect(Iterator&Countable $value): void
			{
			}

			/**
			 * @return Iterator<int, User>
			 */
			public function getIterator(): Iterator
			{
				yield $this->user;
			}
		}
		PHP,
];

yield 'alreaddy fixed' => [
	<<<'PHP'
		<?php

		namespace App;

		use App\Model\User;
		use App\Service\UserManager;
		/**
		 * This is an exceptional test classs.
		 */
		class Test extends \RuntimeException implements \IteratorAggregate
		{
			/**
			 * @var \DateTime
			 */
			private \DateTime $date;

			/**
			 * @var User
			 */
			private User $user;

			private string $class = \DateTime::class;

			/**
			 * @param \DateTime|\DateTimeImmutable $date
			 *
			 * @return \DateTime
			 */
			public function run(\DateTime|\DateTimeImmutable $date): \DateTime
			{
				if ($date instanceof \DateTimeImmutable) {
					throw new \RuntimeException();
				}

				\DateTime::createFromFormat('Y-m-d', '2026-01-01');

				new \DateTime();
				new \DateTimeImmutable();

				$manager = new UserManager();

				return new \DateTime();
			}

			/**
			 * @param \Iterator&\Countable $value
			 */
			public function intersect(\Iterator&\Countable $value): void
			{
			}

			/**
			 * @return \Iterator<int, User>
			 */
			public function getIterator(): \Iterator
			{
				yield $this->user;
			}
		}
		PHP,
];
	}

	/******************
	 * Internal methods
	 ******************/

	protected function createFixer(): AbstractFixer
	{
		return new GlobalNativeNamespaceImportFixer();
	}
}
