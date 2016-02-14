<?php
/**
 * Example settings file config.php:
 * 
 * ```php
 * return [
 *      // License key for Bitrix
 *      'licenseKey' => 'NFR-123-456',
 * 
 *      // Modules to be installed
 *      'modules' => [
 *          'project.main',
 *          'bex.bbc',
 *          'digitalwand.admin_helper',
 *      ],
 * 
 *      // Options for modules 
 *      'options' => [
 *          'vendor.module' => [
 *              'OPTION_CODE' => 'value',
 *              'OPTION_CODE' => ['value' => 'test', 'siteId' => 's1']
 *          ],
 *      ],
 * 
 *      // Settings for module "cluster"
 *      'cluster' => [
 *          'memcache' => [
 *              [
 *                  'GROUP_ID' => 1,
 *                  'HOST' => 'host',
 *                  'PORT' => 'port',
 *                  'WEIGHT' => 'weight',
 *                  'STATUS' => 'status',
 *              ],
 *              [
 *                  'GROUP_ID' => 1,
 *                  'HOST' => 'host',
 *                  'PORT' => 'port',
 *                  'WEIGHT' => 'weight',
 *                  'STATUS' => 'status',
 *              ]
 *          ]
 *      ],
 * 
 *      // Values for file .settings.php
 *      'settings' => [
 *          'connections' => [
 *              'default' => [
 *                  'host' => 'host',
 *                  'database' => 'db',
 *                  'login' => 'login',
 *                  'password' => 'pass',
 *                  'className' => '\\Bitrix\\Main\\DB\\MysqlConnection',
 *                  'options' => 2,
 *              ],
 *          ]
 *      ]
 * ];
 * ```
 */

return [
];