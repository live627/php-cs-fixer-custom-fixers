<?php

declare(strict_types=1);

require __DIR__ . '/AbstractIntegrationTestCase.php';
//~ use PhpCsFixer\Tests\Test\AbstractIntegrationTestCase;
use PhpCsFixer\Tests\Test\IntegrationCase;
use PhpCsFixer\Tests\Test\IntegrationCaseFactoryInterface;
use PhpCsFixer\Tests\Test\InternalIntegrationCaseFactory;

final class IntegrationTest extends AbstractIntegrationTestCase
{
	/*************************
	 * Internal static methods
	 *************************/

	protected static function getFixturesDir(): string
	{
		return __DIR__ . '/fixtures';
	}

	protected static function getTempFile(): string
	{
		return sys_get_temp_dir() . '/MyClass.php';
	}

	protected static function createIntegrationCaseFactory(): IntegrationCaseFactoryInterface
	{
		return new InternalIntegrationCaseFactory();
	}

	protected static function assertRevertedOrderFixing(IntegrationCase $case, string $fixed_input_code, string $fixed_input_code_with_reversed_fixers): void
	{
		parent::assertRevertedOrderFixing($case, $fixed_input_code, $fixed_input_code_with_reversed_fixers);

		$settings = $case->getSettings();

		if (!isset($settings['isExplicitPriorityCheck'])) {
			self::markTestIncomplete('Missing `isExplicitPriorityCheck` extension setting.');
		}

		if ($settings['isExplicitPriorityCheck']) {
			self::assertNotSame(
				$fixed_input_code,
				$fixed_input_code_with_reversed_fixers,
				sprintf(
					'Test "%s" in "%s" is expected to be priority check, but fixers applied in reversed order made the same changes.',
					$case->getTitle(),
					$case->getFileName(),
				),
			);
		}
	}
}
