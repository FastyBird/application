<?php declare(strict_types = 1);

/**
 * BootstrapExtension.php
 *
 * @license        More in license.md
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
			'sentry' => Schema\Expect::structure(
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

		if (is_string(getenv('FB_APP_PARAMETER__SENTRY_DSN')) && getenv('FB_APP_PARAMETER__SENTRY_DSN') !== '') {
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
				->setArgument('level', Monolog\Logger::WARNING);

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

		/** @var string|null $sentryHandlerServiceName */
		$sentryHandlerServiceName = $builder->getByType(Sentry\Monolog\Handler::class, false);

		if ($sentryHandlerServiceName !== null) {
			/** @var DI\Definitions\ServiceDefinition $sentryHandlerService */
			$sentryHandlerService = $builder->getDefinition($sentryHandlerServiceName);

			/** @var string $monologLoggerServiceName */
			$monologLoggerServiceName = $builder->getByType(Monolog\Logger::class, true);

			/** @var DI\Definitions\ServiceDefinition $monologLoggerService */
			$monologLoggerService = $builder->getDefinition($monologLoggerServiceName);

			$monologLoggerService->addSetup('?->pushHandler(?)', ['@self', $sentryHandlerService]);
		}
	}

}
