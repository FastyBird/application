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

use Dotenv\Dotenv;
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

if ($autoload === null) {
	echo "Autoload file not found; try 'composer dump-autoload' first." . PHP_EOL;

	exit(1);
}

require $autoload;

if (getenv('FB_APP_DIR') !== FALSE) {
	$envDirs = getenv('FB_APP_DIR') . '/env';

} else {
	$envDirs = [__DIR__ . '/../../env', __DIR__ . '/../../../../env'];
}

$envLocation = null;

foreach ($envDirs as $envDir) {
	if (is_dir($envDir) && realpath($envDir) !== null) {
		$envLocation = $envDir;
		break;
	}
}

if ($envLocation !== null) {
	try {
		$dotEnv = Dotenv::createImmutable($envLocation);
		$dotEnv->load();

	} catch (Throwable $ex) {
		// Env files could not be loaded
	}
}

$container = Boot\Bootstrap::boot()
	->createContainer();

/** @var Console\Application $console */
$console = $container->getByType(Console\Application::class);

exit($console->run());
