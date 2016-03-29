<?php
/**
 * For the full copyright and license information, please view the LICENSE.md
 * file that was distributed with this source code.
 */

namespace Notamedia\ConsoleJedi\Module\Exception;

class ModuleException extends \RuntimeException
{
	/**
	 * ModuleException constructor.
	 *
	 * @param string $message
	 * @param int $moduleName
	 * @param \Exception|null $previous
	 */
	public function __construct($message, $moduleName, \Exception $previous = null)
	{
		parent::__construct("[$moduleName] " . $message, 0, $previous);
	}
}