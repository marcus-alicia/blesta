<?php
namespace Blesta\Core\Util\Input\Fields;
use Blesta\Core\Util\Input\Fields\Common\FieldsInterface;
use Blesta\Core\Util\Input\Fields\Common\FieldInterface;

/**
 * Input Fields
 *
 * Provides the structure for inputs to set which fields to appear when
 * interacting with the input via Blesta in various places.
 *
 * @package blesta
 * @subpackage blesta.core.Util.Input.Fields
 * @copyright Copyright (c) 2020, Phillips Data, Inc.
 * @license http://www.blesta.com/license/ The Blesta License Agreement
 * @link http://www.blesta.com/ Blesta
 * @see InputField
 */
class InputFields implements FieldsInterface
{
    /**
     * @var array An array of FieldInterface objects, each representing a single input field and (optionally) its label
     */
    private $fields = [];
    /**
     * @var string A string of HTML markup to include when outputting the fields. This can include things such as
     *  javascript.
     */
    private $html = null;

    /**
     * Returns an array of fields set for this group of fields
     *
     * @return array An array of FieldInterface objects set for this group of fields
     */
    public function getFields()
    {
        return $this->fields;
    }

    /**
     * Returns an HTML content set for this group of fields
     *
     * @return string The HTML content set for this group of fields
     */
    public function getHtml()
    {
        return $this->html;
    }

    /**
     * Sets HTML content to be rendered into the view when outputting the fields.
     * This is intended to allow for the inclusion of javascript to dynamically
     * handle the rendering of the fields, but is not limited to such.
     *
     * @param string $html The HTML content to render into the view when outputting the fields
     */
    public function setHtml($html)
    {
        $this->html = $html;
    }

    /**
     * Sets the field into the collection of FieldInterface objects
     *
     * @param FieldInterface $field A FieldInterface object to be passed set into the list of FieldInterface objects
     */
    public function setField(FieldInterface $field)
    {
        $this->fields[] = $field;
    }

    /**
     * Creates a label with the given name and marks it for the given field
     *
     * @param string $name The name of this label
     * @param string $for The ID of the form element this label is part of
     * @param array $attributes Attributes for this label
     * @param bool $preserve_tags True to preserve tags in the label name
     * @return InputField An InputField object to be passed into one of the various field methods to assign this
     *  label to that field
     */
    public function label($name, $for = null, array $attributes = null, $preserve_tags = false)
    {
        $label = new InputField('label');
        $label->setParam('name', $name);
        $label->setParam('for', $for);
        $label->setParam('attributes', $attributes);
        $label->setParam('preserve_tags', $preserve_tags);

        return $label;
    }

    /**
     * Creates a tooltip with the given message
     *
     * @param string $message The tooltip message
     * @return InputField An InputField object that can be attached to an InputField label
     */
    public function tooltip($message)
    {
        $tooltip = new InputField('tooltip');
        $tooltip->setParam('message', $message);

        return $tooltip;
    }

    /**
     * Creates a text input field
     *
     * @param string $name The name to set in the HTML name field
     * @param string $value The value to set in the HTML value field
     * @param array $attributes Attributes for this input field
     * @param FieldInterface $label A FieldInterface object representing the label to attach to
     *  this field (see InputFields::label)
     * @return InputField An InputField object that can be attached to a FieldInterface label
     * @see InputFields::label()
     * @see InputField::attach()
     */
    public function fieldText($name, $value = null, $attributes = [], FieldInterface $label = null)
    {
        $field = new InputField('fieldText');
        $field->setParam('name', $name);
        $field->setParam('value', $value);
        $field->setParam('attributes', $attributes);

        if ($label) {
            $field->setLabel($label);
        }

        return $field;
    }

    /**
     * Creates a number input field
     *
     * @param string $name The name to set in the HTML name field
     * @param string $value The value to set in the HTML value field
     * @param int $min Specifies the minimum value allowed
     * @param int $max Specifies the maximum value allowed
     * @param int $step Specifies the legal number intervals
     * @param array $attributes Attributes for this input field
     * @param FieldInterface $label A FieldInterface object representing the label to attach to
     *  this field (see InputFields::label)
     * @return InputField An InputField object that can be attached to a FieldInterface label
     * @see InputFields::label()
     * @see InputField::attach()
     */
    public function fieldNumber($name, $value = null, $min = null, $max = null, $step = null, $attributes = [], FieldInterface $label = null)
    {
        $field = new InputField('fieldNumber');
        $field->setParam('name', $name);
        $field->setParam('value', $value);
        $field->setParam('min', $min);
        $field->setParam('max', $max);
        $field->setParam('step', $step);
        $field->setParam('attributes', $attributes);

        if ($label) {
            $field->setLabel($label);
        }

        return $field;
    }
    
    /**
     * Creates a hidden input field
     *
     * @param string $name The name to set in the HTML name field
     * @param string $value The value to set in the HTML value field
     * @param array $attributes Attributes for this input field
     * @return InputField An InputField object that can be attached to an InputField label
     * @see InputFields::label()
     * @see InputField::attach()
     */
    public function fieldHidden($name, $value = null, $attributes = [])
    {
        $field = new InputField('fieldHidden');
        $field->setParam('name', $name);
        $field->setParam('value', $value);
        $field->setParam('attributes', $attributes);

        return $field;
    }

    /**
     * Creates an image input field
     *
     * @param string $name The name to set in the HTML name field
     * @param string $value The value to set in the HTML value field
     * @param array $attributes Attributes for this input field
     * @param FieldInterface $label A FieldInterface object representing the label to attach to
     *  this field (see InputFields::label)
     * @return InputField An InputField object that can be attached to a FieldInterface label
     * @see InputFields::label()
     * @see InputField::attach()
     */
    public function fieldImage($name, $value = null, $attributes = [], FieldInterface $label = null)
    {
        $field = new InputField('fieldImage');
        $field->setParam('name', $name);
        $field->setParam('value', $value);
        $field->setParam('attributes', $attributes);

        if ($label) {
            $field->setLabel($label);
        }

        return $field;
    }

    /**
     * Creates a reset input field
     *
     * @param string $name The name to set in the HTML name field
     * @param string $value The value to set in the HTML value field
     * @param array $attributes Attributes for this input field
     * @param FieldInterface $label A FieldInterface object representing the label to attach to
     *  this field (see InputFields::label)
     * @return InputField An InputField object that can be attached to a FieldInterface label
     * @see InputFields::label()
     * @see InputField::attach()
     */
    public function fieldReset($name, $value = null, $attributes = [], FieldInterface $label = null)
    {
        $field = new InputField('fieldReset');
        $field->setParam('name', $name);
        $field->setParam('value', $value);
        $field->setParam('attributes', $attributes);

        if ($label) {
            $field->setLabel($label);
        }

        return $field;
    }

    /**
     * Creates a checkbox
     *
     * @param string $name The name to set in the HTML name field
     * @param string $value The value to set in the HTML value field
     * @param bool $checked True to set this field as checked
     * @param array $attributes Attributes for this input field
     * @param FieldInterface $label A FieldInterface object representing the label to attach to
     *  this field (see InputFields::label)
     * @return InputField An InputField object that can be attached to a FieldInterface label
     * @see InputFields::label()
     * @see InputField::attach()
     */
    public function fieldCheckbox(
        $name,
        $value = null,
        $checked = false,
        $attributes = [],
        FieldInterface $label = null
    ) {
        $field = new InputField('fieldCheckbox');
        $field->setParam('name', $name);
        $field->setParam('value', $value);
        $field->setParam('checked', $checked);
        $field->setParam('attributes', $attributes);

        if ($label) {
            $field->setLabel($label);
        }

        return $field;
    }

    /**
     * Creates a radio box
     *
     * @param string $name The name to set in the HTML name field
     * @param string $value The value to set in the HTML value field
     * @param bool $checked True to set this field as checked
     * @param array $attributes Attributes for this input field
     * @param FieldInterface $label A FieldInterface object representing the label to attach to
     *  this field (see InputFields::label)
     * @return InputField An InputField object that can be attached to a FieldInterface label
     * @see InputFields::label()
     * @see InputField::attach()
     */
    public function fieldRadio($name, $value = null, $checked = false, $attributes = [], FieldInterface $label = null)
    {
        $field = new InputField('fieldRadio');
        $field->setParam('name', $name);
        $field->setParam('value', $value);
        $field->setParam('checked', $checked);
        $field->setParam('attributes', $attributes);

        if ($label) {
            $field->setLabel($label);
        }

        return $field;
    }

    /**
     * Creates a textarea field
     *
     * @param string $name The name to set in the HTML name field
     * @param string $value The value to set in this textarea
     * @param array $attributes Attributes for this input field
     * @param FieldInterface $label A FieldInterface object representing the label to attach to
     *  this field (see InputFields::label)
     * @return InputField An InputField object that can be attached to a FieldInterface label
     * @see InputFields::label()
     * @see InputField::attach()
     */
    public function fieldTextarea($name, $value = null, $attributes = [], FieldInterface $label = null)
    {
        $field = new InputField('fieldTextarea');
        $field->setParam('name', $name);
        $field->setParam('value', $value);
        $field->setParam('attributes', $attributes);

        if ($label) {
            $field->setLabel($label);
        }

        return $field;
    }

    /**
     * Creates a password input field
     *
     * @param string $name The name to set in the HTML name field
     * @param array $attributes Attributes for this input field
     * @param FieldInterface $label A FieldInterface object representing the label to attach to
     *  this field (see InputFields::label)
     * @return InputField An InputField object that can be attached to a FieldInterface label
     * @see InputFields::label()
     * @see InputField::attach()
     */
    public function fieldPassword($name, $attributes = [], FieldInterface $label = null)
    {
        $field = new InputField('fieldPassword');
        $field->setParam('name', $name);
        $field->setParam('attributes', $attributes);

        if ($label) {
            $field->setLabel($label);
        }

        return $field;
    }

    /**
     * Creates a file input field
     *
     * @param string $name The name to set in the HTML name field
     * @param array $attributes Attributes for this input field
     * @param FieldInterface $label A FieldInterface object representing the label to attach to
     *  this field (see InputFields::label)
     * @return InputField An InputField object that can be attached to a FieldInterface label
     * @see InputFields::label()
     * @see InputField::attach()
     */
    public function fieldFile($name, $attributes = [], FieldInterface $label = null)
    {
        $field = new InputField('fieldFile');
        $field->setParam('name', $name);
        $field->setParam('attributes', $attributes);

        if ($label) {
            $field->setLabel($label);
        }

        return $field;
    }

    /**
     * Creates a select list
     *
     * @param string $name The name to set in the HTML name field
     * @param array $options The options to place in this select list
     * @param mixed $selected_value The option(s) to set as selected
     * @param array $attributes Attributes for this input field
     * @param array $option_attributes Attributes for each option to set. If single dimension will set the attributes
     *  for every option, if multi-dimensional will set option for each element key that matches in $options
     * @param FieldInterface $label A FieldInterface object representing the label to attach to
     *  this field (see InputFields::label)
     * @return InputField An InputField object that can be attached to a FieldInterface label
     * @see InputFields::label()
     * @see InputField::attach()
     */
    public function fieldSelect(
        $name,
        $options = [],
        $selected_value = null,
        $attributes = [],
        $option_attributes = [],
        FieldInterface $label = null
    ) {
        $field = new InputField('fieldSelect');
        $field->setParam('name', $name);
        $field->setParam('options', $options);
        $field->setParam('selected_value', $selected_value);
        $field->setParam('attributes', $attributes);
        $field->setParam('option_attributes', $option_attributes);

        if ($label) {
            $field->setLabel($label);
        }

        return $field;
    }

    /**
     * Creates a select list with multiple selectable options
     *
     * @param string $name The name to set in the HTML name field
     * @param array $options The options to place in this select list
     * @param string $selected_values The options to set as selected
     * @param array $attributes Attributes for this input field
     * @param array $option_attributes Attributes for each option to set. If single dimension will set the attributes
     *  for every option, if multi-dimensional will set option for each element key that matches in $options
     * @param FieldInterface $label A FieldInterface object representing the label to attach to
     *  this field (see InputFields::label)
     * @return InputField An InputField object that can be attached to a FieldInterface label
     * @see InputFields::label()
     * @see InputField::attach()
     */
    public function fieldMultiSelect(
        $name,
        $options = [],
        $selected_values = [],
        $attributes = [],
        $option_attributes = [],
        FieldInterface $label = null
    ) {
        $field = new InputField('fieldMultiSelect');
        $field->setParam('name', $name);
        $field->setParam('options', $options);
        $field->setParam('selected_values', $selected_values);
        $field->setParam('attributes', $attributes);
        $field->setParam('option_attributes', $option_attributes);

        if ($label) {
            $field->setLabel($label);
        }

        return $field;
    }

    /**
     * Creates a button with default type=button, can be overriden by attirbutes
     *
     * @param string $name The name to set in the HTML name field
     * @param string $value The value to set in the HTML value field
     * @param array $attributes Attributes for this input field
     * @param FieldInterface $label A FieldInterface object representing the label to attach to
     *  this field (see InputFields::label)
     * @return InputField An InputField object that can be attached to a FieldInterface label
     * @see InputFields::label()
     * @see InputField::attach()
     */
    public function fieldButton($name, $value = null, $attributes = [], FieldInterface $label = null)
    {
        $field = new InputField('fieldButton');
        $field->setParam('name', $name);
        $field->setParam('value', $value);
        $field->setParam('attributes', $attributes);

        if ($label) {
            $field->setLabel($label);
        }

        return $field;
    }

    /**
     * Creates a button of type submit
     *
     * @param string $name The name to set in the HTML name field
     * @param string $value The value to set in the HTML value field
     * @param array $attributes Attributes for this input field
     * @param FieldInterface $label A FieldInterface object representing the label to attach to
     *  this field (see InputFields::label)
     * @return InputField An InputField object that can be attached to a FieldInterface label
     * @see InputFields::label()
     * @see InputField::attach()
     */
    public function fieldSubmit($name, $value = null, $attributes = [], FieldInterface $label = null)
    {
        $field = new InputField('fieldSubmit');
        $field->setParam('name', $name);
        $field->setParam('value', $value);
        $field->setParam('attributes', $attributes);

        if ($label) {
            $field->setLabel($label);
        }

        return $field;
    }
}


