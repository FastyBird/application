<?php declare(strict_types = 1);

/**
 * Bootstrap.php
 *
 * @license        More in license.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:Bootstrap!
 * @subpackage     Boot
 * @since          0.1.0
 *
 * @date           08.03.20
 */

namespace FastyBird\Bootstrap\Boot;

use FastyBird\Bootstrap\Exceptions;
use Nette\Configurator;
use Tester;

/**
 * Service bootstrap configurator
 *
 * @package        FastyBird:Bootstrap!
 * @subpackage     Boot
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
class Bootstrap
{

	/**
	 * @param string $envPrefix
	 *
	 * @return Configurator
	 */
	public static function boot(string $envPrefix = 'FB_APP_PARAMETER_'): Configurator
	{
		self::initConstants();

		// Create app configurator
		$configurator = new Configurator();

		// Define variables
		$configurator->addParameters([
			'tempDir' => FB_TEMP_DIR,
			'logsDir' => FB_LOGS_DIR,
			'appDir'  => FB_APP_DIR,
		]);

		// Load parameters from environment
		$configurator->addParameters(self::loadEnvParameters($envPrefix));

		if (!class_exists('\Tester\Environment') || getenv(Tester\Environment::RUNNER) === false) {
			$configurator->enableTracy(FB_LOGS_DIR);
		}

		$configurator->setTimeZone('UTC');

		// Default extension config
		$configurator->addConfig(__DIR__ . DS . '..' . DS . '..' . DS . 'config' . DS . 'common.neon');
		$configurator->addConfig(__DIR__ . DS . '..' . DS . '..' . DS . 'config' . DS . 'defaults.neon');

		if (file_exists(FB_CONFIG_DIR . DS . 'common.neon')) {
			$configurator->addConfig(FB_CONFIG_DIR . DS . 'common.neon');
		}

		if (file_exists(FB_CONFIG_DIR . DS . 'defaults.neon')) {
			$configurator->addConfig(FB_CONFIG_DIR . DS . 'defaults.neon');
		}

		if (file_exists(FB_CONFIG_DIR . DS . 'local.neon')) {
			$configurator->addConfig(FB_CONFIG_DIR . DS . 'local.neon');
		}

		return $configurator;
	}

	/**
	 * @return void
	 */
	private static function initConstants(): void
	{
		// Define shorter constant for OS directory separator
		if (!defined('DS')) {
			define('DS', DIRECTORY_SEPARATOR);
		}

		// Configuring APP dir path
		if (getenv('FB_APP_DIR') !== false && !defined('FB_APP_DIR')) {
			define('FB_APP_DIR', getenv('FB_APP_DIR'));

		} elseif (!defined('FB_APP_DIR')) {
			define('FB_APP_DIR', __DIR__ . '/../../../../..');
		}

		// Configuring resources dir path
		if (getenv('FB_RESOURCES_DIR') !== false && !defined('FB_RESOURCES_DIR')) {
			define('FB_RESOURCES_DIR', getenv('FB_RESOURCES_DIR'));

		} elseif (!defined('FB_RESOURCES_DIR')) {
			define('FB_RESOURCES_DIR', FB_APP_DIR . DS . 'resources');
		}

		// Configuring storage dir path
		if (getenv('FB_STORAGE_DIR') !== false && !defined('FB_STORAGE_DIR')) {
			define('FB_STORAGE_DIR', getenv('FB_STORAGE_DIR'));

		} elseif (!defined('FB_STORAGE_DIR')) {
			define('FB_STORAGE_DIR', FB_APP_DIR . DS . 'var');
		}

		// Check for storage dir
		if (!is_dir(FB_STORAGE_DIR)) {
			mkdir(FB_STORAGE_DIR, 0777, true);
		}

		// Configuring temporary dir path
		if (getenv('FB_TEMP_DIR') !== false && !defined('FB_TEMP_DIR')) {
			define('FB_TEMP_DIR', getenv('FB_TEMP_DIR'));

		} elseif (!defined('FB_TEMP_DIR')) {
			define('FB_TEMP_DIR', FB_STORAGE_DIR . DS . 'temp');
		}

		// Check for temporary dir
		if (!is_dir(FB_TEMP_DIR)) {
			mkdir(FB_TEMP_DIR, 0777, true);
		}

		// Configuring logs dir path
		if (getenv('FB_LOGS_DIR') !== false && !defined('FB_LOGS_DIR')) {
			define('FB_LOGS_DIR', getenv('FB_LOGS_DIR'));

		} elseif (!defined('FB_LOGS_DIR')) {
			define('FB_LOGS_DIR', FB_STORAGE_DIR . DS . 'logs');
		}

		// Check for logs dir
		if (!is_dir(FB_LOGS_DIR)) {
			mkdir(FB_LOGS_DIR, 0777, true);
		}

		// Configuring configuration dir path
		if (getenv('FB_CONFIG_DIR') !== false && !defined('FB_CONFIG_DIR')) {
			define('FB_CONFIG_DIR', getenv('FB_CONFIG_DIR'));

		} elseif (!defined('FB_CONFIG_DIR')) {
			define('FB_CONFIG_DIR', FB_APP_DIR . DS . 'config');
		}

		// Check for temporary dir
		if (!is_dir(FB_CONFIG_DIR)) {
			mkdir(FB_CONFIG_DIR, 0777, true);
		}
	}

	/**
	 * @param string $prefix
	 * @param string $delimiter
	 *
	 * @return mixed[]
	 */
	private static function loadEnvParameters(
		string $prefix,
		string $delimiter = '_'
	): array {
		if ($delimiter === '') {
			throw new Exceptions\InvalidArgumentException('Delimiter must be non-empty string');
		}

		$prefix .= $delimiter;

		$map = static function (&$array, array $keys, $value) use (&$map) {
			if (count($keys) <= 0) {
				return is_numeric($value) ? (int) $value : $value;
			}

			$key = array_shift($keys);

			if (!is_array($array)) {
				throw new Exceptions\InvalidStateException(sprintf('Invalid structure for key "%s" value "%s"', implode($keys), $value));
			}

			if (!array_key_exists($key, $array)) {
				$array[$key] = [];
			}

			// Recursive
			$array[$key] = $map($array[$key], $keys, $value);

			return $array;
		};

		$parameters = [];

		foreach (getenv() as $key => $value) {
			if (strpos($key, $prefix) === 0) {
				// Parse PREFIX{delimiter=_}{NAME-1}{delimiter=_}{NAME-N}
				$keys = explode($delimiter, strtolower(substr($key, strlen($prefix))));

				// Check if delimiter is ok and keys were exploded
				if ($keys === false) {
					continue;
				}

				// Make array structure
				$map($parameters, $keys, $value);
			}
		}

		return $parameters;
	}

}
