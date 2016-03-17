<?php
/**
 * For the full copyright and license information, please view the LICENSE.md
 * file that was distributed with this source code.
 */

namespace Notamedia\ConsoleJedi\Console\Command\Module;

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
	 * Проверяет есть ли исключение старого ядра битрикс, если есть, выбрасывает исключение
	 *
	 * @param string $message [не обязательный] Дололнительное описание обстоятельств ошибки
	 * @param \CMain $APPLICATION [не обязательный] Объект $APPLICATION
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