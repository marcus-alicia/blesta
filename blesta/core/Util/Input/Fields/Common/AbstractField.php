<?php
namespace Blesta\Core\Util\Input\Fields\Common;

/**
 * Abstract Field
 *
 * @package blesta
 * @subpackage blesta.core.Util.Input.Fields.Common
 * @copyright Copyright (c) 2020, Phillips Data, Inc.
 * @license http://www.blesta.com/license/ The Blesta License Agreement
 * @link http://www.blesta.com/ Blesta
 * @see FieldsInterface
 */
abstract class AbstractField implements FieldInterface
{
    public $type;

    /**
     * Constructs a new AbstractField of the given type. Types directly correlate
     * to Form helper method names.
     *
     * @param string $type The type of FieldInterface
     */
    public function __construct($type)
    {
        $this->type = $type;
    }
}