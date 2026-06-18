<?php

declare(strict_types=1);

use Live627\PhpCsFixer\CustomFixers\SnakeCaseIdentifiersFixer;
use PhpCsFixer\AbstractFixer;
use PhpCsFixer\Tests\Test\AbstractFixerWithAliasedOptionsTestCase;
use PHPUnit\Framework\Attributes\DataProvider;

final class SnakeCaseIdentifiersFixerTest extends AbstractFixerWithAliasedOptionsTestCase
{
	#[DataProvider('provideFixCases')]
	/****************
	 * Public methods
	 ****************/

	public function testFix(string $expected, ?string $input = null, ?array $configuration = null): void
	{
		if ($configuration !== null) {
			$this->fixer->configure($configuration);
		}

		$this->doTest($expected, $input);
	}

	/***********************
	 * Public static methods
	 ***********************/

	/**
	 * @return iterable<array{0: string, 1?: string}>
	 */
	public static function provideFixCases(): iterable
	{
		yield [
			'<?php $member_name = 1;',
			'<?php $memberName = 1;',
		];

		yield [
			'<?php $member_name = 1;',
			'<?php $MemberName = 1;',
		];

		yield [
			'<?php $user_id = 1;',
			'<?php $userID = 1;',
		];

		yield [
			'<?php $xml_parser = 1;',
			'<?php $XMLParser = 1;',
		];

		yield 'phpdoc rename' => [
			<<<'PHP'
				<?php
				/**
				 * @param string $member_name
				 */
				function test($member_name) {}
				PHP,
			<<<'PHP'
				<?php
				/**
				 * @param string $memberName
				 */
				function test($memberName) {}
				PHP,
		];

		yield 'multiple variables' => [
			'<?php $member_name = $user_id + $display_name;',
			'<?php $memberName = $userId + $displayName;',
		];

		yield 'phpdoc and variables' => [
			<<<'PHP'
				<?php

				/**
				 * @param string $member_name
				 */
				function test($member_name)
				{
					$current_user = $member_name;
				}
				PHP,
			<<<'PHP'
				<?php

				/**
				 * @param string $memberName
				 */
				function test($memberName)
				{
					$currentUser = $memberName;
				}
				PHP,
		];

		yield 'excluded variable' => [
			'<?php $memberName = 1;',
			null,
			[
				'exclude' => [
					'$memberName',
				],
			],
		];

		yield 'excluded pattern' => [
			'<?php $txtBirthday = 1;',
			null,
			[
				'exclude_patterns' => [
					'/^\$txt[A-Z]/',
				],
			],
		];
	}

	/******************
	 * Internal methods
	 ******************/

	protected function createFixer(): AbstractFixer
	{
		return new SnakeCaseIdentifiersFixer();
	}
}
