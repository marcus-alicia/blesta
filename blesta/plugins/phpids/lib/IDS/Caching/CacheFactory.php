<?php
/**
 * PHPIDS
 *
 * Requirements: PHP5, SimpleXML
 *
 * Copyright (c) 2008 PHPIDS group (https://phpids.org)
 *
 * PHPIDS is free software; you can redistribute it and/or modify
 * it under the terms of the GNU Lesser General Public License as published by
 * the Free Software Foundation, version 3 of the License, or
 * (at your option) any later version.
 *
 * PHPIDS is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Lesser General Public License for more details.
 *
 * You should have received a copy of the GNU Lesser General Public License
 * along with PHPIDS. If not, see <http://www.gnu.org/licenses/>.
 *
 * PHP version 5.1.6+
 *
 * @category Security
 * @package  PHPIDS
 * @author   Mario Heiderich <mario.heiderich@gmail.com>
 * @author   Christian Matthies <ch0012@gmail.com>
 * @author   Lars Strojny <lars@strojny.net>
 * @license  http://www.gnu.org/licenses/lgpl.html LGPL
 * @link     http://php-ids.org/
 */

namespace IDS\Caching;

/**
 * Caching factory
 *
 * This class is used as a factory to load the correct concrete caching
 * implementation.
 *
 * @category  Security
 * @package   PHPIDS
 * @author    Christian Matthies <ch0012@gmail.com>
 * @author    Mario Heiderich <mario.heiderich@gmail.com>
 * @author    Lars Strojny <lars@strojny.net>
 * @copyright 2007-2009 The PHPIDS Group
 * @license   http://www.gnu.org/licenses/lgpl.html LGPL
 * @link      http://php-ids.org/
 * @since     Version 0.4
 */
class CacheFactory
{
    /**
     * Factory method
     *
     * @param object $init the IDS_Init object
     * @param string $type the caching type
     *
     * @return object the caching facility
     */
    public static function factory($init, $type)
    {
        $object  = false;
        $wrapper = preg_replace(
            '/\W+/m',
            '',
            ucfirst($init->config['Caching']['caching'])
        );
        $class   = '\\IDS\\Caching\\' . $wrapper . 'Cache';
        $path    = dirname(__FILE__) . DIRECTORY_SEPARATOR . $wrapper . 'Cache.php';

        if (file_exists($path)) {
            include_once $path;

            if (class_exists($class)) {
                $object = call_user_func(
                    array('' . $class, 'getInstance'),
                    $type,
                    $init
                );
            }
        }

        return $object;
    }
}
