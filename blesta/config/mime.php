<?php
/**
 * Allowed MIME types
 *
 * @package blesta
 * @subpackage blesta.config
 * @copyright Copyright (c) 2010, Phillips Data, Inc.
 * @license http://www.blesta.com/license/ The Blesta License Agreement
 * @link http://www.blesta.com/ Blesta
 */

// Set the allowed MIME types
Configure::set('Blesta.allowed_mime_types', [
    'image' => [
        'image/gif',
        'image/jpeg',
        'image/png',
        'image/svg+xml',
        'application/svg+xml'
    ]
]);