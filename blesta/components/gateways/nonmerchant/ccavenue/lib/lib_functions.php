<?php
/**
 * LibFunctions Helper functions
 *
 * @package blesta
 * @subpackage blesta.components.gateways.ccavenue.lib
 * @author Phillips Data, Inc.
 * @author Nirays Technologies
 * @copyright Copyright (c) 2013, Phillips Data, Inc.
 * @license http://www.blesta.com/license/ The Blesta License Agreement
 * @link http://www.blesta.com/ Blesta
 * @link http://nirays.com/ Nirays
 */
class LibFunctions
{
    /**
     * Construct a new merchant gateway
     */
    public function __construct()
    {
    }

    /**
     * To verify if the checksum is valid
     * @param $MerchantId
     * @param $OrderId
     * @param $Amount
     * @param $AuthDesc
     * @param $WorkingKey
     * @param $CheckSum
     * @return bool
     */
    public function verifyChecksum($MerchantId, $OrderId, $Amount, $AuthDesc, $WorkingKey, $CheckSum)
    {
        $str = "$MerchantId|$OrderId|$Amount|$AuthDesc|$WorkingKey";
        $adler = 1;
        $adler = $this->adler32($adler, $str);
        if ($adler === $CheckSum) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * To create checksum
     * @param $MerchantId
     * @param $OrderId
     * @param $Amount
     * @param $redirectUrl
     * @param $WorkingKey
     * @return int
     */
    public function getChecksum($MerchantId, $OrderId, $Amount, $redirectUrl, $WorkingKey)
    {
        $str = "$MerchantId|$OrderId|$Amount|$redirectUrl|$WorkingKey";
        $adler = 1;
        $adler = $this->adler32($adler, $str);
        return $adler;
    }

    /**
     * Adder logic
     * @param $adler
     * @param $str
     * @return int
     */
    private function adler32($adler, $str)
    {
        $BASE =  65521 ;
        $s1 = $adler & 0xffff ;
        $s2 = ($adler >> 16) & 0xffff;
        for ($i = 0; $i < strlen($str); $i++) {
            $s1 = ($s1 + Ord($str[$i])) % $BASE;
            $s2 = ($s2 + $s1) % $BASE;
        }
        return $this->leftShift($s2, 16) + $s1;
    }

    /**
     * Left Shift logic
     * @param $str
     * @param $num
     * @return int
     */
    private function leftShift($str, $num)
    {
        $str = DecBin($str);

        for ($i = 0; $i < (64 - strlen($str)); $i++) {
            $str = '0' . $str;
        }

        for ($i = 0; $i < $num; $i++) {
            $str = $str . '0';
            $str = substr($str, 1);
        }
        return $this->cdec($str);
    }

    /**
     * @param $num
     * @return int
     */
    private function cdec($num)
    {
        $dec=0;
        for ($n = 0; $n < strlen($num); $n++) {
            $temp = $num[$n];
            $dec =  $dec + $temp * pow(2, strlen($num) - $n - 1);
        }
        return $dec;
    }
}
