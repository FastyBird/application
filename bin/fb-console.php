<?php
/**
 * console.php
 *
 * @license        More in license.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:Bootstrap!
 * @subpackage     bin
 * @since          0.1.0
 *
 * @date           08.03.20
 */

declare(strict_types = 1);

use FastyBird\Bootstrap\Boot;
use Symfony\Component\Console;

$autoload = null;

$autoloadFiles = [__DIR__ . '/../vendor/autoload.php', __DIR__ . '/../../../autoload.php',];

foreach ($autoloadFiles as $autoloadFile) {
	if (file_exists($autoloadFile)) {
		$autoload = $autoloadFile;
		break;
	}
}

if (!$autoload) {
	echo "Autoload file not found; try 'composer dump-autoload' first." . PHP_EOL;

	exit(1);
}

require $autoload;

$container = Boot\Bootstrap::boot()
	->createContainer();

/** @var Console\Application $console */
$console = $container->getByType(Console\Application::class);

exit($console->run());
