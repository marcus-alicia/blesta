<?php

class AppModel extends Model
{
    use Blesta\Core\Util\Common\Traits\Container;
    private $per_page = NULL;
    protected $logger = NULL;
    protected $replacement_keys = NULL;
    public function __construct($db_info = NULL)
    {
        parent::__construct($db_info);
        Loader::loadComponents($this, ["Input", "Record"]);
        Loader::loadHelpers($this, ["DataStructure", "Date"]);
        Language::loadLang(["_global", "_custom"]);
        $this->logger = $this->getFromContainer("logger");
        $this->replacement_keys = Configure::get("Blesta.replacement_keys");
        $this->setPerPage(Configure::get("Blesta.results_per_page"));
    }
    public function getPerPage()
    {
        return $this->per_page;
    }
    public function setPerPage($per_page)
    {
        $this->per_page = max(1, (int) $per_page);
    }
    public function currencyToDecimal($value, $currency, $decimals = NULL)
    {
        if (!isset($this->CurrencyFormat)) {
            Loader::loadHelpers($this, ["CurrencyFormat" => [Configure::get("Blesta.company_id")]]);
        }
        return $this->CurrencyFormat->cast($value, $currency, $decimals);
    }
    public static function truncateDecimal($value, $min, $decimal_char = ".")
    {
        $exponent = strrpos($value, $decimal_char);
        if ($exponent === false) {
            return $value;
        }
        return rtrim(str_pad(rtrim($value, "0"), $exponent + 1 + $min, "0", STR_PAD_RIGHT), $decimal_char);
    }
    public function dateToUtc($date, $format = "Y-m-d H:i:s", $use_cur_time = false)
    {
        $dt = clone $this->Date;
        try {
            if ($use_cur_time && $dt->format("H:i:s", $date) == "00:00:00") {
                $date = $dt->format("Y-m-d", $date);
                $dt->setTimezone("UTC", Configure::get("Blesta.company_timezone"));
                $date .= " " . $dt->format("H:i:s");
            }
            $dt->setTimezone(Configure::get("Blesta.company_timezone"), "UTC");
            return $dt->format($format, $date);
        } catch (Throwable $e) {
            $this->Input->setErrors(["date" => ["message" => $e->getMessage()]]);
        }
    }
    public function errors()
    {
        if (is_object($this->Input) && $this->Input instanceof Input) {
            return $this->Input->errors();
        }
    }
    protected function setRulesIfSet($rules, $status = true)
    {
        foreach ($rules as $field => $types) {
            foreach ($types as $key => $value) {
                $rules[$field][$key]["if_set"] = is_bool($status) ? $status : true;
            }
        }
        return $rules;
    }
    protected function _($name)
    {
        $args = func_get_args();
        $first = array_shift($args);
        array_unshift($args, $first, true);
        return call_user_func_array(["Language", "_"], $args);
    }
    protected function ifSet(&$str, $alt = "")
    {
        return isset($str) ? $str : $alt;
    }
    public function setDefaultIfEmpty($value)
    {
        if ($value === "") {
            return Record::keywordValue("DEFAULT");
        }
        return $value;
    }
    public function validateExists($value, $field, $table, $allow_empty = true)
    {
        if (!$allow_empty && empty($value)) {
            return true;
        }
        $count = $this->Record->select()->from($table)->where($field, "=", $value)->numResults();
        if (0 < $count) {
            return true;
        }
        return false;
    }
    public function validateStateCountry($state, $country)
    {
        $count = $this->Record->select("countries.alpha2")->from("states")->innerJoin("countries", "countries.alpha2", "=", "states.country_alpha2", false)->where("states.code", "=", $state)->open()->where("countries.alpha2", "=", $country)->orWhere("countries.alpha3", "=", $country)->close()->numResults();
        if (0 < $count) {
            return true;
        }
        return false;
    }
    public function systemEncrypt($value, $key = NULL, $iv = NULL)
    {
        $this->loadCrypto();
        $key = $key !== NULL ? $key : Configure::get("Blesta.system_key");
        $iv = $iv !== NULL ? $iv : Configure::get("Blesta.system_key");
        $this->Crypt_Hash->setHash("sha256");
        $this->Crypt_Hash->setKey("systemEncrypt");
        $hash = $this->Crypt_Hash->hash($key);
        $this->Crypt_AES->setKey($hash);
        $this->Crypt_AES->setIV($iv);
        return base64_encode($this->Crypt_AES->encrypt($value));
    }
    public function systemDecrypt($value, $key = NULL, $iv = NULL)
    {
        if ($value == "") {
            return $value;
        }
        $this->loadCrypto();
        $key = $key !== NULL ? $key : Configure::get("Blesta.system_key");
        $iv = $iv !== NULL ? $iv : Configure::get("Blesta.system_key");
        $this->Crypt_Hash->setHash("sha256");
        $this->Crypt_Hash->setKey("systemEncrypt");
        $hash = $this->Crypt_Hash->hash($key);
        $this->Crypt_AES->setKey($hash);
        $this->Crypt_AES->setIV($iv);
        return $this->Crypt_AES->decrypt(base64_decode($value));
    }
    public function systemHash($value, $key = NULL, $hash = "sha256")
    {
        $this->loadCrypto();
        $key = $key !== NULL ? $key : Configure::get("Blesta.system_key");
        $this->Crypt_Hash->setHash($hash);
        $this->Crypt_Hash->setKey($key);
        return bin2hex($this->Crypt_Hash->hash($value));
    }
    public function boolToInt($value)
    {
        if (!$value || $value === "false") {
            return 0;
        }
        return 1;
    }
    public function strToBool($value)
    {
        if (!$value || $value === "false") {
            return false;
        }
        return true;
    }
    protected function loadCrypto($other_libs = NULL)
    {
        if (!isset($this->Security)) {
            Loader::loadComponents($this, ["Security"]);
        }
        if (!isset($this->Crypt_AES)) {
            $this->Crypt_AES = $this->Security->create("Crypt", "AES");
        }
        if (!isset($this->Crypt_Hash)) {
            $this->Crypt_Hash = $this->Security->create("Crypt", "Hash");
        }
        if ($other_libs) {
            foreach ($other_libs as $lib) {
                $lib_name = "Crypt_" . $lib;
                if (!isset($this->{$lib_name})) {
                    $this->{$lib_name} = $this->Security->create("Crypt", $lib);
                }
            }
        }
    }
    protected function applyFilters(Record $record, $filters = [])
    {
        foreach ($filters as $filter_key => $filter) {
            if (is_array($filter) && isset($filter["column"]) && isset($filter["operator"]) && isset($filter["value"])) {
                $record->where($filter["column"], $filter["operator"], $filter["value"]);
            } else {
                if (is_array($filter)) {
                    $table = $filter_key;
                    foreach ($filter as $column => $value) {
                        if (is_array($value) && isset($value["column"]) && isset($value["operator"]) && isset($value["value"])) {
                            $record->where($table . "." . $value["column"], $value["operator"], $value["value"]);
                        } else {
                            $record->where($table . "." . $column, "=", $value);
                        }
                    }
                } else {
                    $record->where($filter_key, "=", $filter);
                }
            }
        }
        return $record;
    }
    public function executeAndParseEvent($event_name, $params = [])
    {
        $eventFactory = $this->getFromContainer("util.events");
        $eventListener = $eventFactory->listener();
        $eventListener->register($event_name);
        $event = $eventListener->trigger($eventFactory->event($event_name, $params));
        $returnValue = $event->getReturnValue();
        $return = ["__return__" => $returnValue];
        if (is_array($returnValue)) {
            foreach ($returnValue as $key => $data) {
                if (array_key_exists($key, $params)) {
                    $return[$key] = $data;
                }
            }
        }
        return $return;
    }
}

?>
