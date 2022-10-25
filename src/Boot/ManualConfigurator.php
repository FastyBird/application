<?php declare(strict_types = 1);

/**
 * ManualConfigurator.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:Bootstrap!
 * @subpackage     Boot
 * @since          0.1.0
 *
 * @date           25.10.22
 */

namespace FastyBird\Bootstrap\Boot;

use Nette\DI\Config\Adapter;

/**
 * Container manual configurator
 *
 * @package        FastyBird:Bootstrap!
 * @subpackage     Boot
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class ManualConfigurator extends BaseConfigurator
{

	/** @var Array<int|string, string> */
	private array $configs = [];

	public function addConfig(string $configFile): void
	{
		$this->configs[] = $configFile;
	}

	public function addConfigAdapter(string $extension, Adapter $adapter): void
	{
		$this->configAdapters[$extension] = $adapter;
	}

	/**
	 * {@inheritDoc}
	 */
	protected function loadConfigFiles(): array
	{
		return $this->configs;
	}

}
