<?php
/**
 * For the full copyright and license information, please view the LICENSE.md
 * file that was distributed with this source code.
 */

namespace Notamedia\ConsoleJedi;

use Bitrix\Main;

class Loader
{
    public function loadTests()
    {
        spl_autoload_register(function ($className) {
            $file = ltrim($className, "\\");
            $file = strtr($file, Main\Loader::ALPHA_UPPER, Main\Loader::ALPHA_LOWER);
            $file = str_replace('\\', '/', $file);

            if (substr($file, -5) === 'table')
            {
                $file = substr($file, 0, -5);
            }

            $arFile = explode('/', $file);

            if (preg_match("#[^\\\\/a-zA-Z0-9_]#", $file))
            {
                return false;
            }
            elseif ($arFile[0] === 'bitrix')
            {
                return false;
            }
            elseif ($arFile[2] !== 'tests')
            {
                return false;
            }

            $module = array_shift($arFile) . '.' . array_shift($arFile);

            if (!Main\Loader::includeModule($module))
            {
                return false;
            }

            $file = $module . '/' . implode('/', $arFile) . '.php';
            $bitrixPath = Main\Application::getDocumentRoot() . '/' . Main\Loader::BITRIX_HOLDER . '/modules/' . $file;
            $localPath = Main\Application::getDocumentRoot() . '/' . Main\Loader::LOCAL_HOLDER . '/modules/' . $file;

            if (file_exists($bitrixPath))
            {
                include_once $bitrixPath;
            }
            elseif (file_exists($localPath))
            {
                include_once $localPath;
            }
        });
    }
}