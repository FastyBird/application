<?php declare(strict_types = 1);

namespace Tests\Cases;

use FastyBird\Bootstrap\Boot;
use Ninjify\Nunjuck\TestCase\BaseTestCase;
use Tester\Assert;

require_once __DIR__ . '/../../../bootstrap.php';

/**
 * @testCase
 */
final class ExtensionTest extends BaseTestCase
{

	public function testCompilersServices(): void
	{
		$configurator = Boot\Bootstrap::boot();

		$container = $configurator->createContainer();

		Assert::true(true);
	}

}

$test_case = new ExtensionTest();
$test_case->run();
