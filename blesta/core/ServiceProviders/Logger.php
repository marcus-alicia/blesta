<?php
namespace Blesta\Core\ServiceProviders;

use Pimple\ServiceProviderInterface;
use Pimple\Container;
use Monolog\ErrorHandler;
use Monolog\Logger as Monologger;
use Monolog\Handler\RotatingFileHandler;
use Monolog\Formatter\LineFormatter;
use PDOException;

/**
 * Logger service provider
 *
 * @package blesta
 * @subpackage blesta.core.ServiceProviders
 * @copyright Copyright (c) 2019, Phillips Data, Inc.
 * @license http://www.blesta.com/license/ The Blesta License Agreement
 * @link http://www.blesta.com/ Blesta
 */
class Logger implements ServiceProviderInterface
{
    /**
     * @var Pimple\Container An instance of the container
     */
    private $container;

    /**
     * {@inheritdoc}
     */
    public function register(Container $container)
    {
        $this->container = $container;

        // Create the loggers
        $loggers = ['general'];
        foreach ($loggers as $logger) {
            call_user_func([$this, $logger]);
        }
    }

    /**
     * Set the general channel logger for the application
     */
    private function general()
    {
        $this->container->set('logger', function ($c) {
            // Fetch the log directory and rotation policy settings
            $connection = $c->get('pdo');
            try {
                $query = $connection->prepare('SELECT * FROM `settings` WHERE `settings`.`key` IN (?,?)');
                $query->execute(['log_dir', 'log_days']);
                $settings = $query->fetchAll();
            } catch (PDOException $e) {
                // Unable to fetch from the database
                $settings = [];
            }

            $log = ['dir' => '', 'days' => 30];
            foreach ($settings as $setting) {
                $key = ($setting->key == 'log_dir' ? 'dir' : 'days');
                $log[$key] = $setting->value;
            }

            // Determine if this request is through the cron
            $uri = '';
            if (isset($_SERVER['REQUEST_URI']) && !empty($_SERVER['REQUEST_URI'])) {
                $uri = $_SERVER['REQUEST_URI'];
            } elseif (isset($_SERVER['argv']) && !empty($_SERVER['argv'][1])) {
                $uri = $_SERVER['argv'][1];
            }
            $log_postfix = str_contains($uri, 'cron') ? '-cron' : '';

            // Set the rotating file handler for each level
            if (!defined('JSON_INVALID_UTF8_SUBSTITUTE')) {
                define('JSON_INVALID_UTF8_SUBSTITUTE', 0);
            }

            $handlers = [];
            $formatter = new LineFormatter(null, null, false, true);

            $levels = [
                'emergency' => Monologger::EMERGENCY,
                'alert' => Monologger::ALERT,
                'critical' => Monologger::CRITICAL,
                'error' => Monologger::ERROR,
                'warning' => Monologger::WARNING,
                'notice' => Monologger::NOTICE,
                'info' => Monologger::INFO,
                'debug' => Monologger::DEBUG
            ];
            foreach ($levels as $type => $level) {
                // We must be able to write to the directory to set the handler
                // otherwise no logs could be written
                $log_dir = rtrim($log['dir'], DIRECTORY_SEPARATOR);
                if (empty($log_dir) || !is_dir($log_dir) || !is_writable($log_dir)) {
                    continue;
                }

                // Create a file handler for each level type
                $handler = new RotatingFileHandler(
                    $log_dir . DIRECTORY_SEPARATOR . 'general-' . $type . $log_postfix . '.log',
                    (is_numeric($log['days']) ? (int)$log['days'] : 0),
                    $level,
                    false
                );

                $handler->setFormatter($formatter);
                $handlers[] = $handler;
            }

            $logger = new Monologger('general', $handlers);

            // Have the general log automatically log php errors
            $errorHandler = new ErrorHandler($logger);
            $errorHandler->registerErrorHandler([], true, -1, false);
            $errorHandler->registerExceptionHandler();
            $errorHandler->registerFatalHandler();

            return $logger;
        });
    }
}
