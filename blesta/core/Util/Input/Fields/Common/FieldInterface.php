<?php
namespace Blesta\Core\Util\Input\Fields\Common;

/**
 * Field Interface
 *
 * Stores information regarding a particular Field, which may consist of
 * a label, tooltip, input field, or some combination thereof.
 *
 * @package blesta
 * @subpackage blesta.core.Util.Input.Fields.Common
 * @copyright Copyright (c) 2020, Phillips Data, Inc.
 * @license http://www.blesta.com/license/ The Blesta License Agreement
 * @link http://www.blesta.com/ Blesta
 * @see FieldsInterface
 */
interface FieldInterface
{
    /**
     * Sets all parameters for this FieldInterface, which will be dispatched to
     * the appropriate Form helper method when needed, or to the tooltip.
     *
     * @param string $name The name of the parameter. For tooltip types this should be 'message'
     * @param mixed $value The value of the parameter
     * @return FieldInterface
     */
    public function setParam($name, $value);

    /**
     * Sets the label associated with this specific field.
     *
     * @param FieldInterface $label The FieldInterface label to associated with this field
     * @return FieldInterface
     */
    public function setLabel(FieldInterface $label);

    /**
     * Attaches a field to a label FieldInterface, or a tooltip to a label FieldInterface.
     * Only field and tooltip types can be attached to a label. So the current
     * object must be of type "label". And $field must be of some other type.
     *
     * @param FieldInterface $field The FieldInterface to attach to this label
     * @return FieldInterface
     */
    public function attach(FieldInterface $field);
}
