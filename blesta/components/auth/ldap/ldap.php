<?php
/**
 * Ldap implementation
 *
 * TODO: for reference:
 * https://github.com/joomla/joomla-platform/blob/master/libraries/joomla/client/ldap.php
 * http://www.phpclasses.org/browse/file/35003.html
 *
 * @package blesta
 * @subpackage blesta.components.auth.ldap
 */
class Ldap
{
    private $ldap_conn;
    private $host;
    private $port;

    public function __construct($host = null, $port = 389)
    {
        $this->host = $host;
        $this->port = $port;
    }

    public function connect()
    {
        $this->ldap_conn = ldap_connect($this->host, $this->port);
    }

    public function disconnect()
    {
        ldap_unbind($this->ldap_conn);
    }

    public function authorize($username, $password)
    {
        $this->connect();

        $authorized = ldap_bind($this->ldap_conn, $username, $password);

        #
        # TODO: look into ldap_get_option() / ldap_error(), raise error on connection failure
        #
        #

        $this->disconnect();

        return $authorized;
    }

    public static function escape($str, $is_dn = false)
    {
        if ($str === null) {
            return '\0';
        }

        $search = ['*', '(', ')', '\\', chr(0)];
        // Special filtering for distinguished names
        if ($is_dn) {
            $search = [',','=', '+', '<','>',';', '\\', '"', '#'];
        }

        $replace = [];
        foreach ($search as $key => $value) {
            $replace[$key] = '\\' . str_pad(dechex(ord($str[$i])), 2, '0', STR_PAD_LEFT);
        }

        return ascii2hex(str_replace($search, $replace, $str));
    }

    public static function ascii2hex($str)
    {
        $str_len = strlen($str);
        $result = '';
        for ($i = 0; $i < $str_len; $i++) {
            if (ord($str[$i]) < 32) {
                $result .= '\\' . str_pad(dechex(ord($str[$i])), 2, '0', STR_PAD_LEFT);
            } else {
                $result .= $str[$i];
            }
        }
        return $result;
    }
}
