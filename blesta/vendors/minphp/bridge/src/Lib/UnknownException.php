<?php

/**
 * Backwards Compatible UnknownException replacement
 */
class UnknownException extends ErrorException
{

    /**
     * Placeholder for backwards compatbility
     *
     * @param int $severity
     * @param string $message
     * @param string $file
     * @param in $line
     */
    public static function setErrorHandler($severity, $message, $file, $line)
    {
        if (error_reporting() & $severity) {
            throw new UnknownException($message, 0, $severity, $file, $line);
        }
    }

    /**
     * Placeholder for backwards compatbility
     *
     * @param Exception|Throwable $e
     */
    public static function setExceptionHandler($e)
    {
        // Require an Exception (php5) or Throwable (php7) object
        if (error_reporting() === 0
            || (!($e instanceof Throwable) && !($e instanceof Exception))
        ) {
            return;
        }

        echo sprintf(
            "Uncaught %s, code %d in %s on line %d \nMessage: %s",
            get_class($e),
            $e->getCode(),
            $e->getFile(),
            $e->getLine(),
            $e->getMessage()
        );
    }

    /**
     * Placeholder for backwards compatbility
     */
    public static function setFatalErrorHandler()
    {
        $error = error_get_last();

        if (!empty($error)
            && ($error['type'] & E_ERROR)
            && (error_reporting() & $error['type'])
        ) {
            try {
                Dispatcher::raiseError(
                    new UnknownException($error['message'], 0, $error['type'], $error['file'], $error['line'])
                );
            } catch (Throwable $e) {
                // Catch 'Error' and 'Exception' in php7
                echo $e->getMessage();
            } catch (Exception $e) {
                // Catch Exceptions in php5, 'Error' is not supported
                echo $e->getMessage();
            }
        }
    }
}
