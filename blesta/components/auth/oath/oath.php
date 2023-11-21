<?php
/**
 * Oath implementation of the OATH specification for both HOTP and TOTP one time
 * passwords (RFC4226 and its extension HOTPTimeBased Internet-Draft,
 * respectively).
 *
 * See http://www.openauthentication.org
 *
 * @package blesta
 * @subpackage blesta.components.auth.oath
 */
class Oath
{
    /**
     * Precomputed power values
     */
    private static $digits_power = [1,10,100,1000,10000,100000,1000000,10000000,100000000];
    /**
     * @var string Shared secret
     */
    private $secret;
    /**
     * @var int Length of one time password
     */
    private $length = 6;
    /**
     * @var int Moving factor of TOTP
     */
    private $moving_factor = 30;
    /**
     * @var int The number of seconds of drift to account for (+/- 3 minutes)
     */
    private $drift = 180;
    /**
     * @var string The hash function to use during the HMAC calculation
     */
    private $crypto = 'sha1';

    /**
     * Construct a new OATH object using the given secret and HMAC hash function
     *
     * @param string $secret The shared secret
     * @param string $crypto The crypto system to use during the HMAC calculation
     */
    public function __construct($secret = null, $crypto = 'sha1')
    {
        $this->setSecret($secret);
        $this->setCrypto($crypto);
    }

    /**
     * Set the secret key to use along with the HMAC calculation
     *
     * @param string $secret The shared secret
     */
    public function setSecret($secret)
    {
        $this->secret = $secret;
    }

    /**
     * Set the algorithm to use during the HMAC calculation
     *
     * @param string $crypto The crypto system to use during the HMAC calculation
     */
    public function setCrypto($crypto)
    {
        $this->crypto = $crypto;
    }

    /**
     * Checks whether the given one time password is a valid HOTP password
     * using the given counter and VLAV (Validation Look Ahead Value)
     *
     * @param string $otp The one time password to validate
     * @param int $counter The counter value
     * @param int $vlav The validation look ahead value
     * @return bool True if this OTP is valid, false otherwise
     */
    public function checkHotp($otp, $counter, $vlav = 0)
    {

        // Convert the secret to a binary string, if not already
        $secret = self::hex2Bin($this->secret);

        // Loop through the C and C+ VLAV to find a valid OTP
        for ($i = 0; $i < $vlav; $i++) {
            if ($otp == self::hotp($secret, ($counter + $i), max(6, strlen($otp)), $this->crypto)) {
                return true;
            }
        }
        return false;
    }

    /**
     * Checks whether the given one time password is a valid TOTP password using the given time
     *
     * @param string $otp The one time password to validate
     * @param int $time The time value
     * @return bool True if this OTP is valid, false otherwise
     */
    public function checkTotp($otp, $time)
    {
        // The number of iterations before and after to check for matches
        $k = (int)($this->drift / $this->moving_factor);

        // Convert the secret to a binary string, if not already
        $secret = self::hex2Bin($this->secret);

        // Loop through the +/- range for a match
        for ($i = -$k; $i < $k; $i++) {
            if ($otp == self::totp(
                $secret,
                ($time + ($this->moving_factor * $i)) / $this->moving_factor,
                max(6, strlen($otp)),
                $this->crypto
            )) {
                return true;
            }
        }
        return false;
    }

    /**
     * Generate an HOTP pass phrase
     *
     * @param string $key The secret key
     * @param int $counter The counter value
     * @param $digit_length The length of the resulting pass phrase
     * @param $crypto The algorithm to use in the HMAC calculation
     * @return string The HOTP pass pharse
     */
    public static function hotp($key, $counter, $digit_length = 6, $crypto = 'sha1')
    {
        $counter = str_pad($counter, 8, '0', STR_PAD_LEFT);
        $bin_counter = self::hexStr2Bin($counter);

        // HMAC
        return self::truncate(hash_hmac($crypto, $bin_counter, $key), $digit_length);
    }

    /**
     * Generate an TOTP pass phrase
     *
     * @param string $key The secret key
     * @param int $counter The counter value (e.g. Unix time)
     * @param $digit_length The length of the resulting pass phrase
     * @param $crypto The algorithm to use in the HMAC calculation
     * @return string The TOTP pass pharse
     */
    public static function totp($key, $counter, $digit_length = 6, $crypto = 'sha1')
    {
        $text = [];
        for ($i = 7; $i >= 0; $i--) {
            $text[] = ($counter & 0xff);
            $counter >>= 8;
        }

        $text = array_reverse($text);
        foreach ($text as $index => $value) {
            $text[$index] = chr($value);
        }

        $text = implode('', $text);

        // HMAC
        return self::truncate(hash_hmac($crypto, $text, $key), $digit_length);
    }

    /**
     * Detects if the given string is in hex format, if not, converts it to hex
     *
     * @param string The string to test/convert to a hex string
     */
    private static function hex2Bin($string)
    {
        if (preg_match('/[^a-f0-9]/i', $string)) {
            // already in hex
            return $string;
        }
        // Not in hex, so convert
        return pack('H*', $string);
    }

    /**
     * Converts a hex string to a binary string
     *
     * @param int $hex The hex value to convert
     * @return string The byte representation of the given hex
     */
    private static function hexStr2Bin($hex)
    {
        $hex = substr($hex, -8);

        $cur_data = array_fill(0, strlen($hex), 0);
        for ($i = strlen($hex) - 1; $i >= 0; $i--) {
            $cur_data[$i] = pack('C*', $hex);
            $hex >>= 8;
        }

        return implode($cur_data);
    }

    /**
     * Truncates the given hash per the RFC4226 truncation method
     *
     * @param string $hash The hash as a hexadecimal string
     * @param int $digit_length The length of the result
     * @return string The truncated hash
     */
    private static function truncate($hash, $digit_length = 6)
    {
        // Convert to decimal
        $hmac_result = [];
        foreach (str_split($hash, 2) as $hex) {
            $hmac_result[] = hexdec($hex);
        }

        // Find offset
        $offset = $hmac_result[count($hmac_result) - 1] & 0xf;

        if (!isset(self::$digits_power[$digit_length])) {
            return null;
        }

        // Truncate
        $result = (
            (($hmac_result[$offset] & 0x7f) << 24) |
            (($hmac_result[$offset + 1] & 0xff) << 16) |
            (($hmac_result[$offset + 2] & 0xff) << 8) |
            ($hmac_result[$offset + 3] & 0xff)
        ) % self::$digits_power[$digit_length];

        return str_pad($result, $digit_length, '0', STR_PAD_LEFT);
    }
}
