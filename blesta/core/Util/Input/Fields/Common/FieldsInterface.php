<?php
namespace Blesta\Core\Util\Input\Fields\Common;

/**
 * Fields Interface
 *
 * Provides the structure for inputs to set which fields to appear when
 * interacting with the input via Blesta in various places.
 *
 * @package blesta
 * @subpackage blesta.core.Util.Input.Fields.Common
 * @copyright Copyright (c) 2020, Phillips Data, Inc.
 * @license http://www.blesta.com/license/ The Blesta License Agreement
 * @link http://www.blesta.com/ Blesta
 * @see FieldInterface
 */
interface FieldsInterface
{
    /**
     * Creates a label with the given name and marks it for the given field
     *
     * @param string $name The name of this label
     * @param string $for The ID of the form element this label is part of
     * @param array $attributes Attributes for this label
     * @param bool $preserve_tags True to preserve tags in the label name
     * @return FieldInterface A FieldInterface object to be passed into one of the various field methods to assign this
     *  label to that field
     */
    public function label($name, $for = null, array $attributes = null, $preserve_tags = false);

    /**
     * Creates a tooltip with the given message
     *
     * @param string $message The tooltip message
     * @return FieldInterface A FieldInterface object that can be attached to a FieldInterface label
     */
    public function tooltip($message);

    /**
     * Creates a text input field
     *
     * @param string $name The name to set in the HTML name field
     * @param string $value The value to set in the HTML value field
     * @param array $attributes Attributes for this input field
     * @param FieldInterface $label A FieldInterface object representing the label to attach to
     *  this field (see FieldsInterface::label)
     * @return FieldInterface A FieldInterface object that can be attached to a FieldInterface label
     * @see FieldsInterface::label()
     * @see FieldInterface::attach()
     */
    public function fieldText($name, $value = null, $attributes = [], FieldInterface $label = null);

    /**
     * Creates a hidden input field
     *
     * @param string $name The name to set in the HTML name field
     * @param string $value The value to set in the HTML value field
     * @param array $attributes Attributes for this input field
     * @return FieldInterface A FieldInterface object that can be attached to a FieldInterface label
     * @see FieldsInterface::label()
     * @see FieldInterface::attach()
     */
    public function fieldHidden($name, $value = null, $attributes = []);

    /**
     * Creates an image input field
     *
     * @param string $name The name to set in the HTML name field
     * @param string $value The value to set in the HTML value field
     * @param array $attributes Attributes for this input field
     * @param FieldInterface $label A FieldInterface object representing the label to attach to
     *  this field (see FieldsInterface::label)
     * @return FieldInterface A FieldInterface object that can be attached to a FieldInterface label
     * @see FieldsInterface::label()
     * @see FieldInterface::attach()
     */
    public function fieldImage($name, $value = null, $attributes = [], FieldInterface $label = null);

    /**
     * Creates a reset input field
     *
     * @param string $name The name to set in the HTML name field
     * @param string $value The value to set in the HTML value field
     * @param array $attributes Attributes for this input field
     * @param FieldInterface $label A FieldInterface object representing the label to attach to
     *  this field (see FieldsInterface::label)
     * @return FieldInterface A FieldInterface object that can be attached to a FieldInterface label
     * @see FieldsInterface::label()
     * @see FieldInterface::attach()
     */
    public function fieldReset($name, $value = null, $attributes = [], FieldInterface $label = null);

    /**
     * Creates a checkbox
     *
     * @param string $name The name to set in the HTML name field
     * @param string $value The value to set in the HTML value field
     * @param bool $checked True to set this field as checked
     * @param array $attributes Attributes for this input field
     * @param FieldInterface $label A FieldInterface object representing the label to attach to
     *  this field (see FieldsInterface::label)
     * @return FieldInterface A FieldInterface object that can be attached to a FieldInterface label
     * @see FieldsInterface::label()
     * @see FieldInterface::attach()
     */
    public function fieldCheckbox(
        $name,
        $value = null,
        $checked = false,
        $attributes = [],
        FieldInterface $label = null
    );

    /**
     * Creates a radio box
     *
     * @param string $name The name to set in the HTML name field
     * @param string $value The value to set in the HTML value field
     * @param bool $checked True to set this field as checked
     * @param array $attributes Attributes for this input field
     * @param FieldInterface $label A FieldInterface object representing the label to attach to
     *  this field (see FieldsInterface::label)
     * @return FieldInterface A FieldInterface object that can be attached to a FieldInterface label
     * @see FieldsInterface::label()
     * @see FieldInterface::attach()
     */
    public function fieldRadio(
        $name,
        $value = null,
        $checked = false,
        $attributes = [],
        FieldInterface $label = null
    );

    /**
     * Creates a textarea field
     *
     * @param string $name The name to set in the HTML name field
     * @param string $value The value to set in this textarea
     * @param array $attributes Attributes for this input field
     * @param FieldInterface $label A FieldInterface object representing the label to attach to
     *  this field (see FieldsInterface::label)
     * @return FieldInterface A FieldInterface object that can be attached to a FieldInterface label
     * @see FieldsInterface::label()
     * @see FieldInterface::attach()
     */
    public function fieldTextarea($name, $value = null, $attributes = [], FieldInterface $label = null);

    /**
     * Creates a password input field
     *
     * @param string $name The name to set in the HTML name field
     * @param array $attributes Attributes for this input field
     * @param FieldInterface $label A FieldInterface object representing the label to attach to
     *  this field (see FieldsInterface::label)
     * @return FieldInterface A FieldInterface object that can be attached to a FieldInterface label
     * @see FieldsInterface::label()
     * @see FieldInterface::attach()
     */
    public function fieldPassword($name, $attributes = [], FieldInterface $label = null);

    /**
     * Creates a file input field
     *
     * @param string $name The name to set in the HTML name field
     * @param array $attributes Attributes for this input field
     * @param FieldInterface $label A FieldInterface object representing the label to attach to
     *  this field (see FieldsInterface::label)
     * @return FieldInterface A FieldInterface object that can be attached to a FieldInterface label
     * @see FieldsInterface::label()
     * @see FieldInterface::attach()
     */
    public function fieldFile($name, $attributes = [], FieldInterface $label = null);

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
     *  this field (see FieldsInterface::label)
     * @return FieldInterface A FieldInterface object that can be attached to a FieldInterface label
     * @see FieldsInterface::label()
     * @see FieldInterface::attach()
     */
    public function fieldSelect(
        $name,
        $options = [],
        $selected_value = null,
        $attributes = [],
        $option_attributes = [],
        FieldInterface $label = null
    );

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
     *  this field (see FieldsInterface::label)
     * @return FieldInterface A FieldInterface object that can be attached to a FieldInterface label
     * @see FieldsInterface::label()
     * @see FieldInterface::attach()
     */
    public function fieldMultiSelect(
        $name,
        $options = [],
        $selected_values = [],
        $attributes = [],
        $option_attributes = [],
        FieldInterface $label = null
    );

    /**
     * Creates a button with default type=button, can be overriden by attirbutes
     *
     * @param string $name The name to set in the HTML name field
     * @param string $value The value to set in the HTML value field
     * @param array $attributes Attributes for this input field
     * @param FieldInterface $label A FieldInterface object representing the label to attach to
     *  this field (see FieldsInterface::label)
     * @return FieldInterface A FieldInterface object that can be attached to a FieldInterface label
     * @see FieldsInterface::label()
     * @see FieldInterface::attach()
     */
    public function fieldButton($name, $value = null, $attributes = [], FieldInterface $label = null);

    /**
     * Creates a button of type submit
     *
     * @param string $name The name to set in the HTML name field
     * @param string $value The value to set in the HTML value field
     * @param array $attributes Attributes for this input field
     * @param FieldInterface $label A FieldInterface object representing the label to attach to
     *  this field (see FieldsInterface::label)
     * @return FieldInterface A FieldInterface object that can be attached to a FieldInterface label
     * @see FieldsInterface::label()
     * @see FieldInterface::attach()
     */
    public function fieldSubmit($name, $value = null, $attributes = [], FieldInterface $label = null);
}
