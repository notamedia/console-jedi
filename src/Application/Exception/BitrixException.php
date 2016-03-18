<?php
/**
 * For the full copyright and license information, please view the LICENSE.md
 * file that was distributed with this source code.
 */

namespace Notamedia\ConsoleJedi\Application\Exception;

/**
 * Wrap for legacy bitrix core exceptions
 *
 * Throws exception with $APPLICATION->GetException() message
 *
 */
class BitrixException extends \RuntimeException
{
	public static function hasException(\CMain $APPLICATION = null)
	{
		if (null === $APPLICATION)
		{
			$APPLICATION = $GLOBALS['APPLICATION'];
		}

		return is_object($APPLICATION->GetException());
	}

	/**
	 * Check for legacy bitrix exception, throws new self if any
	 *
	 * @param string $message [optional] Additional error message
	 * @param \CMain $APPLICATION [optional] $APPLICATION instance
	 * @throws static
	 */
	public static function generate($message = null, \CMain $APPLICATION = null)
	{
		if (null === $APPLICATION)
		{
			$APPLICATION = $GLOBALS['APPLICATION'];
		}

		if ($ex = $APPLICATION->GetException())
		{
			throw new static($message ? $message . ': ' . $ex->GetString() : $ex->GetString());
		}
		else
		{
			throw new static($message ? $message : 'Unknown exception');
		}
	}
}