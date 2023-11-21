<?php
namespace Blesta\Core\Util\Input\Fields;
use Blesta\Core\Util\Input\Fields\Common\FieldInterface;
use Blesta\Core\Util\Input\Fields\Common\AbstractField;

/**
 * Input Field
 *
 * Stores information regarding a particular Input Field, which may consist of
 * a label, tooltip, input field, or some combination thereof.
 *
 * @package blesta
 * @subpackage blesta.core.Util.Input.Fields
 * @copyright Copyright (c) 2020, Phillips Data, Inc.
 * @license http://www.blesta.com/license/ The Blesta License Agreement
 * @link http://www.blesta.com/ Blesta
 * @see InputFields
 */
class InputField extends AbstractField
{
    /**
     * @var array All parameters set for this InputField
     */
    public $params = [];

    /**
     * @var array All fields or tooltips attached to this label
     */
    public $fields = [];

    /**
     * Sets all parameters for this InputField, which will be dispatched to
     * the appropriate Form helper method when needed, or to the tooltip.
     *
     * @param string $name The name of the parameter. For tooltip types this should be 'message'
     * @param mixed $value The value of the parameter
     * @return InputField
     */
    public function setParam($name, $value)
    {
        $this->params[$name] = $value;
        return $this;
    }

    /**
     * Sets the label associated with this specific field.
     *
     * @param FieldInterface $label The FieldInterface label to associated with this field
     * @return InputField
     */
    public function setLabel(FieldInterface $label)
    {
        $this->label = $label;
        return $this;
    }

    /**
     * Attaches a field to a label FieldInterface, or a tooltip to a label FieldInterface.
     * Only field and tooltip types can be attached to a label. So the current
     * object must be of type "label". And $field must be of some other type.
     *
     * @param FieldInterface $field The FieldInterface to attach to this label
     * @return InputField
     */
    public function attach(FieldInterface $field)
    {
        if ($this->type != 'label') {
            return false;
        }
        if ($field->type == 'label') {
            return false;
        }

        if (!isset($this->fields)) {
            $this->fields = [];
        }

        $this->fields[] = $field;
        return $this;
    }
}
