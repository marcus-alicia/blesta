<?php
Configure::set('stripe.map', [
    'gateway' => 'stripe_payments',
    'type' => 'merchant',
    'gateway_meta' => [
        (object)['key' => 'publishable_key', 'value' => 'publishableKey', 'serialized' => 0, 'encrypted' => 0, 'decrypt' => true],
        (object)['key' => 'secret_key', 'value' => 'secretKey', 'serialized' => 0, 'encrypted' => 1, 'decrypt' => true]
    ],
    'accounts_cc' => [
        'first_name' => 'firstname',
        'last_name' => 'lastname',
        'address1' => 'address1',
        'address2' => 'address2',
        'city' => 'city',
        'state' => 'state',
        'zip' => 'postcode',
        'country' => 'country',
        'number' => 'cardNumber',
        'expiration' => 'expiry_date',
        'last4' => 'last_four',
        'type' => 'card_type',
        'client_reference_id' => 'customer',
        'reference_id' => 'method'
    ]
]);
