<?php
namespace Blesta\Core\Util\DataFeed;

use Blesta\Core\Util\DataFeed\Common\AbstractDataFeed;
use Loader;

/**
 * Data feed Factory
 *
 * Creates new feed instances
 *
 * @package blesta
 * @subpackage blesta.core.Util.DataFeed.Feeds
 * @copyright Copyright (c) 2022, Phillips Data, Inc.
 * @license http://www.blesta.com/license/ The Blesta License Agreement
 * @link http://www.blesta.com/ Blesta
 */
class DataFeedFactory
{
    /**
     * Creates an instance of the provided data feed
     */
    public function build($name, array $options = [], $dir = null)
    {
        // Load data feed if not part of the core
        if (!is_null($dir)) {
            $file_name = Loader::fromCamelCase(trim($name, '\\'));
            Loader::load(PLUGINDIR . $dir . DS . 'lib' . DS . $file_name . '.php');
        }

        if (class_exists($name) && (new $name()) instanceof AbstractDataFeed) {
            $class = $name;
        } else {
            $class = '\\Blesta\\Core\\Util\\DataFeed\\Feeds\\' . $name;
        }

        if (class_exists($class) && (new $class()) instanceof AbstractDataFeed) {
            $feed = new $class();

            if (!empty($options)) {
                call_user_func_array([$feed, 'setOptions'], [$options]);
            }

            return $feed;
        }
    }
}
