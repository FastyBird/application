<?php declare(strict_types = 1);

/**
 * RequestHandler.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:Bootstrap!
 * @subpackage     Events
 * @since          0.1.0
 *
 * @date           15.04.20
 */

namespace FastyBird\Bootstrap\Helpers;

use Doctrine\DBAL;
use Doctrine\ORM;
use Doctrine\Persistence;
use FastyBird\Bootstrap\Exceptions;
use Nette;
use Throwable;

/**
 * Database connection helpers
 *
 * @package         FastyBird:Bootstrap!
 * @subpackage      Helpers
 *
 * @author          Adam Kadlec <adam.kadlec@fastybird.com>
 */
class Database
{

	use Nette\SmartObject;

	/** @var Persistence\ManagerRegistry */
	private Persistence\ManagerRegistry $managerRegistry;

	public function __construct(
		Persistence\ManagerRegistry $managerRegistry
	) {
		$this->managerRegistry = $managerRegistry;
	}

	/**
	 * @return bool
	 *
	 * @throws Throwable
	 */
	public function ping(): bool
	{
		$connection = $this->getConnection();

		if ($connection !== null) {
			try {
				$connection->executeQuery($connection->getDatabasePlatform()
					->getDummySelectSQL(), [], []);

			} catch (DBAL\Exception $e) {
				return false;
			}

			return true;
		}

		throw new Exceptions\InvalidStateException('Database connection not found');
	}

	/**
	 * @return DBAL\Connection|null
	 */
	private function getConnection(): ?DBAL\Connection
	{
		$em = $this->getEntityManager();

		if ($em instanceof ORM\EntityManagerInterface) {
			return $em->getConnection();
		}

		return null;
	}

	/**
	 * @return ORM\EntityManagerInterface|null
	 */
	private function getEntityManager(): ?ORM\EntityManagerInterface
	{
		$em = $this->managerRegistry->getManager();

		if ($em instanceof ORM\EntityManagerInterface) {
			if (!$em->isOpen()) {
				$this->managerRegistry->resetManager();

				$em = $this->managerRegistry->getManager();
			}

			if ($em instanceof ORM\EntityManagerInterface) {
				return $em;
			}
		}

		return null;
	}

	/**
	 * @return void
	 *
	 * @throws Throwable
	 */
	public function reconnect(): void
	{
		$connection = $this->getConnection();

		if ($connection !== null) {
			$connection->close();
			$connection->connect();

			return;
		}

		throw new Exceptions\InvalidStateException('Invalid database connection');
	}

	/**
	 * @return void
	 */
	public function clear(): void
	{
		$em = $this->getEntityManager();

		if ($em instanceof ORM\EntityManagerInterface) {
			// Flushing and then clearing Doctrine's entity manager allows
			// for more memory to be released by PHP
			$em->flush();
			$em->clear();

			// Just in case PHP would choose not to run garbage collection,
			// we run it manually at the end of each batch so that memory is
			// regularly released
			gc_collect_cycles();

			return;
		}

		throw new Exceptions\InvalidStateException('Invalid entity manager');
	}

}
