<?php
Configure::set('paypal.map', [
    'gateway' => 'paypal_payments_standard',
    'type' => 'nonmerchant',
    'gateway_meta' => [
        (object)['key' => 'account_id', 'value' => 'email', 'serialized' => 0, 'encrypted' => 1, 'decrypt' => true],
        (object)['key' => 'api_username', 'value' => 'apiusername', 'serialized' => 0, 'encrypted' => 1, 'decrypt' => true],
        (object)['key' => 'api_signature', 'value' => 'apisignature', 'serialized' => 0, 'encrypted' => 1, 'decrypt' => true],
        (object)['key' => 'api_password', 'value' => 'apipassword', 'serialized' => 0, 'encrypted' => 1, 'decrypt' => true],
    ]
]);
