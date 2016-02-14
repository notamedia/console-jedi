<?php
/**
 * The manifest of files that are local to specific environment.
 * This file returns a list of environments that the application
 * may be installed under. The returned data must be in the following
 * format:
 *
 * ```php
 * return [
 *      '<key>' => [
 *          'name' => '<Environment name>',
 *          'path' => '<dir-path>'
 *      ]
 * ];
 * ```
 */

return [
    'dev' => [
        'name' => 'Development',
        'path' => 'dev'
    ],
    'prod' => [
        'name' => 'Production',
        'path' => 'prod',
    ],
];
