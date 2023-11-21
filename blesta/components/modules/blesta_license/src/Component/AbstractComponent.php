<?php
namespace Blesta\Module\BlestaLicense\Component;

use Blesta\ResellerApi\Command\CommandFactory;
use Blesta\ResellerApi\ConnectionInterface;
use Input;

/**
 * Abstract Component
 */
abstract class AbstractComponent
{
    protected $input;
    protected $connection;
    protected $factory;
    protected $moduleRow;

    /**
     * Initialize a component
     *
     * @param Input $input
     * @param ConnectionInterface $connection
     * @param CommandFactory $factory
     * @param stdClass $moduleRow
     */
    public function __construct(
        Input $input,
        ConnectionInterface $connection,
        CommandFactory $factory,
        $moduleRow = null
    ) {
        $this->input = $input;
        $this->connection = $connection;
        $this->factory = $factory;
        $this->moduleRow = $moduleRow;
    }

    /**
     * Returns whether test mode is enabled or not
     *
     * @return boolean True if test mode is enabled, false otherwise
     */
    protected function testModeEnabled()
    {
        return isset($this->moduleRow->meta->test_mode)
            && 'true' === $this->moduleRow->meta->test_mode;
    }

    /**
     * Retrieves a set of Input errors
     *
     * @return array An array of input errors
     */
    public function errors()
    {
        return $this->input->errors();
    }
}
