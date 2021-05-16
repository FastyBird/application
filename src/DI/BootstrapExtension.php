<?php declare(strict_types = 1);

/**
 * BootstrapExtension.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:Bootstrap!
 * @subpackage     DI
 * @since          0.1.0
 *
 * @date           08.03.20
 */

namespace FastyBird\Bootstrap\DI;

use Monolog;
use Nette;
use Nette\DI;
use Nette\Schema;
use Sentry;
use stdClass;

/**
 * App bootstrap extension container
 *
 * @package        FastyBird:Bootstrap!
 * @subpackage     DI
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
class BootstrapExtension extends DI\CompilerExtension
{

	/**
	 * @param Nette\Configurator $config
	 * @param string $extensionName
	 *
	 * @return void
	 */
	public static function register(
		Nette\Configurator $config,
		string $extensionName = 'fbBootstrap'
	): void {
		$config->onCompile[] = function (
			Nette\Configurator $config,
			DI\Compiler $compiler
		) use ($extensionName): void {
			$compiler->addExtension($extensionName, new BootstrapExtension());
		};
	}

	/**
	 * {@inheritdoc}
	 */
	public function getConfigSchema(): Schema\Schema
	{
		return Schema\Expect::structure([
			'logging' => Schema\Expect::structure(
				[
					'level'        => Schema\Expect::int(Monolog\Logger::ERROR),
					'rotatingFile' => Schema\Expect::bool(false),
					'stdOut'       => Schema\Expect::bool(false),
				]
			),
			'sentry'  => Schema\Expect::structure(
				[
					'dsn' => Schema\Expect::string(null),
				]
			),
		]);
	}

	/**
	 * {@inheritdoc}
	 */
	public function loadConfiguration(): void
	{
		$builder = $this->getContainerBuilder();
		/** @var stdClass $configuration */
		$configuration = $this->getConfig();

		// Logger handlers
		if ($configuration->logging->rotatingFile) {
			$builder->addDefinition($this->prefix('logger.handler.rotatingFile'))
				->setType(Monolog\Handler\RotatingFileHandler::class)
				->setArguments([
					'filename' => FB_LOGS_DIR . DS . 'app.log',
					'maxFiles' => 10,
					'level'    => $configuration->logging->level,
				]);
		}

		if ($configuration->logging->stdOut) {
			$builder->addDefinition($this->prefix('logger.handler.stdOut'))
				->setType(Monolog\Handler\StreamHandler::class)
				->setArguments([
					'stream' => 'php://stdout',
					'level'  => $configuration->logging->level,
				]);
		}

		if (
			isset($_ENV['FB_APP_PARAMETER__SENTRY_DSN'])
			&& is_string($_ENV['FB_APP_PARAMETER__SENTRY_DSN'])
			&& $_ENV['FB_APP_PARAMETER__SENTRY_DSN'] !== ''
		) {
			$sentryDSN = $_ENV['FB_APP_PARAMETER__SENTRY_DSN'];

		} elseif (
			getenv('FB_APP_PARAMETER__SENTRY_DSN') !== false
			&& is_string(getenv('FB_APP_PARAMETER__SENTRY_DSN'))
			&& getenv('FB_APP_PARAMETER__SENTRY_DSN') !== ''
		) {
			$sentryDSN = getenv('FB_APP_PARAMETER__SENTRY_DSN');

		} elseif ($configuration->sentry->dsn !== null) {
			$sentryDSN = $configuration->sentry->dsn;

		} else {
			$sentryDSN = null;
		}

		// Sentry issues logger
		if (is_string($sentryDSN) && $sentryDSN !== '') {
			$builder->addDefinition($this->prefix('sentry.handler'))
				->setType(Sentry\Monolog\Handler::class)
				->setArgument('level', $configuration->logging->level);

			$sentryClientBuilderService = $builder->addDefinition('sentry.clientBuilder')
				->setFactory('Sentry\ClientBuilder::create')
				->setArguments([['dsn' => $sentryDSN]]);

			$builder->addDefinition($this->prefix('sentry.client'))
				->setType(Sentry\ClientInterface::class)
				->setFactory([$sentryClientBuilderService, 'getClient']);

			$builder->addDefinition($this->prefix('sentry.hub'))
				->setType(Sentry\State\Hub::class);
		}
	}

	/**
	 * {@inheritdoc}
	 */
	public function beforeCompile(): void
	{
		parent::beforeCompile();

		$builder = $this->getContainerBuilder();
		/** @var stdClass $configuration */
		$configuration = $this->getConfig();

		/** @var string $monologLoggerServiceName */
		$monologLoggerServiceName = $builder->getByType(Monolog\Logger::class);

		/** @var DI\Definitions\ServiceDefinition $monologLoggerService */
		$monologLoggerService = $builder->getDefinition($monologLoggerServiceName);

		if ($configuration->logging->rotatingFile) {
			$rotatingFileHandler = $builder->getDefinition($this->prefix('logger.handler.rotatingFile'));

			$monologLoggerService->addSetup('?->pushHandler(?)', ['@self', $rotatingFileHandler]);
		}

		if ($configuration->logging->stdOut) {
			$stdOutHandler = $builder->getDefinition($this->prefix('logger.handler.stdOut'));

			$monologLoggerService->addSetup('?->pushHandler(?)', ['@self', $stdOutHandler]);
		}

		/** @var string|null $sentryHandlerServiceName */
		$sentryHandlerServiceName = $builder->getByType(Sentry\Monolog\Handler::class, false);

		if ($sentryHandlerServiceName !== null) {
			/** @var DI\Definitions\ServiceDefinition $sentryHandlerService */
			$sentryHandlerService = $builder->getDefinition($this->prefix('sentry.handler'));

			$monologLoggerService->addSetup('?->pushHandler(?)', ['@self', $sentryHandlerService]);
		}
	}

}
