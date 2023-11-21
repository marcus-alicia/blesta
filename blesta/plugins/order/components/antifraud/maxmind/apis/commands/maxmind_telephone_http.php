<?php
/**
 * Maxmind minFraud Telephone Verification
 *
 * @copyright Copyright (c) 2013, Phillips Data, Inc.
 * @license http://opensource.org/licenses/mit-license.php MIT License
 * @package maxmind
 */
class MaxmindTelephoneHttp
{
    /**
     * Initialize the MaxmindTelephoneHttp command
     *
     * @param MaxmindApi The Maxmind API
     */
    public function __construct($api)
    {
        $this->api = $api;
    }

    /**
     * Submits the request to Maxmind and returns the result
     *
     * @param array $data An array of input data including:
     *  - phone Telephone number. For international numbers,
     *      be sure to include the leading "+" sign followed by the country code.
     *  - l Your MaxMind license key.
     *  - verify_code The 4-digit verification code
     *  - delay_time The number of minutes to delay (0 - 30)
     *  - language The language of the call (default English):
     *      - Arabic
     *      - Australian
     *      - Bulgarian
     *      - canadianfrench
     *      - Cantonese
     *      - Catalan
     *      - Chinese
     *      - Croatian
     *      - Czech
     *      - Danish
     *      - Dutch
     *      - egyptian
     *      - English
     *      - englishuk
     *      - Estonian
     *      - Filipino
     *      - Finnish
     *      - French
     *      - German
     *      - Greek
     *      - Hebrew
     *      - Hindi
     *      - Hungarian
     *      - Icelandic
     *      - Indonesian
     *      - Italian
     *      - Japanese
     *      - Korean
     *      - Latvian
     *      - Lingala
     *      - Lithuanian
     *      - Malay
     *      - Mandarin
     *      - Norwegian
     *      - Polish
     *      - Portuguese
     *      - portugueseeu
     *      - Romanian
     *      - Russian
     *      - Serbian
     *      - Slovakian
     *      - Slovenian
     *      - Spanish
     *      - spanisheu
     *      - Swedish
     *      - swiss_german
     *      - Thai
     *      - Turkish
     *      - Ukrainian
     *      - Vietnamese
     * @return MaxmindResponse The response
     */
    public function request($data)
    {
        return $this->api->submit('app/telephone_http', $data);
    }
}
