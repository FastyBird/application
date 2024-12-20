<?php declare(strict_types = 1);

/**
 * TCreatedAt.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:Application!
 * @subpackage     Documents
 * @since          1.0.0
 *
 * @date           03.01.23
 */

namespace FastyBird\Core\Application\Documents;

use DateTimeInterface;

/**
 * Document created date trait
 *
 * @package        FastyBird:Application!
 * @subpackage     Documents
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 *
 * @property-read DateTimeInterface|null $createdAt
 */
trait TCreatedAt
{

	public function getCreatedAt(): DateTimeInterface|null
	{
		return $this->createdAt;
	}

}
