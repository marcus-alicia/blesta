<?php

use Minphp\Form\Form as MinphpForm;

/**
 * Form Bridge
 *
 * Intended for legacy backwards compatibility ONLY.
 * Use Minphp\Form\Form instead.
 */
class Form extends MinphpForm
{
    /**
     * Init
     */
    public function __construct()
    {
        // Minphp/Form differs from the original Form component in minPHP 0.x such
        // that null token key's (the default behavior) is converted to use a form's
        // action, or the request URI, as the key for verification. Thus, it is now
        // required to call Form::setCsrfOptions to set a non-null key in order to
        // maintain backward compatibility with default behavior (a null key).
        $this->setCsrfOptions(['token_key' => '7nE4=3lXeu;K80l?v_F06Eh28JS>L;:1']);
    }
}
