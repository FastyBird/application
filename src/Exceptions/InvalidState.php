<?php declare(strict_types = 1);

/**
 * InvalidState.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:Application!
 * @subpackage     Exceptions
 * @since          1.0.0
 *
 * @date           08.03.20
 */

namespace FastyBird\Core\Application\Exceptions;

use RuntimeException;

class InvalidState extends RuntimeException implements Exception
{

}
