<?php
namespace Blesta\Items\Item;

use Blesta\Items\Item\ItemInterface;
use stdClass;

/**
 * Item
 */
class Item implements ItemInterface
{
    /**
     * @var A set of fields
     */
    private $fields;

    /**
     * Init
     */
    public function __construct()
    {
        $this->fields = new stdClass();
    }

    /**
     * {@inheritdoc}
     */
    public function getFields()
    {
        return $this->fields;
    }

    /**
     * {@inheritdoc}
     */
    public function setFields($fields)
    {
        $this->fields = (object)array_merge((array)$this->fields, (array)$fields);
    }

    /**
     * {@inheritdoc}
     */
    public function setField($key, $value)
    {
        if (strlen($key) > 0) {
            $this->fields->{$key} = $value;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function removeField($key)
    {
        // Ensure even numeric keys are removed
        $fields = (array)$this->fields;
        unset($fields[$key]);
        $this->fields = (object)$fields;
    }
}
