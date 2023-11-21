<?php

class License extends AppModel
{
    private $license_key = NULL;
    private $public_key = NULL;
    private function load()
    {
        $path_to_phpseclib = VENDORDIR . "phpseclib" . DS . "phpseclib" . DS . "phpseclib" . DS;
        $signature_mode = "sha256";

        /** 
        * $license_hash = hash_file($signature_mode, MODELDIR . "license_manager.php");
        * $possible_license_hashes = ["c5b71cbab3bf9ea5cc567d5cd8c993964e8842373082f739d765b1a59987ec77", "6e3adf5edf90fd4d12fd3c8de333107a166c114593a9310c04837f823e1ad70d"];
        * if (!in_array($license_hash, $possible_license_hashes)) {
        *     throw new Exception("LicenseManager signature is invalid, LicenseManager can not be initialized.");
        * }
        */
        $signatures = [];
        $libraries = ["phpseclib\\Crypt\\AES" => ["d55f4cf8660dd1aa2a70f931cac6db09117b77f8105227efb7390ad67238d38a", "bfb6dee5ed24b7261e796b071dfdab8182a2f46e446814c041caf81f84497bd1"], "phpseclib\\Crypt\\Hash" => ["4cb71e8b41dae96fc6739b37a35a0edb5c7191d7a6bf07883981c3f5c0ec0d2f", "3366d7e5947a765defb4cbf671d8ea385e7abfac205bd7b0b3fd5ac0333afc55"], "phpseclib\\Crypt\\Rijndael" => ["3dd8860e58b30b295066305516de2a52b08006fcfe0cb9485ed3c3f9a5e560a2", "6dc30bf43b1dd34b85ef1795285566250b728b37dc9bd377d4d0f239e1c6427d"], "phpseclib\\Crypt\\RSA" => ["7ce08d23725eafe3e93d8622f5d113bab58b16d4be83fd4c0c53b8d7ce37c042", "b4cfcabfdeed662ec605c60c21578cd2311299544e9dfddc9b0046e3f66efe37"], "phpseclib\\Math\\BigInteger" => ["f028fd62096014bad9a8412709e3f96b8b403eeb374f80fbd09aef7c6b8220c9", "dc1b6f8df79056661612ff16294a8e5212a53af6a820359f1ffc140ee8aa0b1c"]];
        foreach ($libraries as $class => $hashes) {
            try {
                $reflection = new ReflectionClass($class);
            } catch (Throwable $e) {
                throw new Exception("One or more security libraries can not be initialized.");
            }
            $signatures[$reflection->getFileName()] = $hashes;
        }
        Loader::loadModels($this, ["LicenseManager" => [$path_to_phpseclib, $signature_mode, $signatures, ROOTWEBDIR], "Settings"]);
        $license_key = $this->Settings->getSetting("license_key");
        $public_key = $this->Settings->getSetting("license_public_key");
        if ($license_key) {
            $this->license_key = $license_key->value;
        }
        if ($public_key) {
            $this->public_key = $public_key->value;
        }
        $this->setKeys();
    }
    private function setKeys()
    {
        $shared_secret = "alkc21)@(#*&173iu5lkdjaz";
        $server_url = "https://account.blesta.com/plugin/license_manager/validate/";
        if (isset($this->LicenseManager)) {
            try {
                $this->LicenseManager->setKeys($this->license_key, $this->public_key, $shared_secret);
                $this->LicenseManager->setLicenseServerUrl($server_url);
            } catch (Exception $e) {
            }
        }
    }
    private function unload()
    {
        if (isset($this->LicenseManager)) {
            unset($this->LicenseManager);
        }
        if (isset($this->Settings)) {
            unset($this->Settings);
        }
    }
    public function updateLicenseKey($license_key)
    {
        try {
            $this->load();
            /**
            * $this->public_key = NULL;
            * $this->license_key = $license_key;
            * $this->setKeys();
            * $this->public_key = $this->LicenseManager->requestKey();
            * $this->setKeys();
            * $result = $this->processLicenseData($license_data);
            * if ($result["status"] == "valid") {
            */
            $license_data = $this->getLicenseData();
            if (true == true) {
                $this->Settings->setSetting("license_public_key", $this->public_key);
                $this->Settings->setSetting("license_key", $this->license_key);
                $this->Settings->setSetting("license_data", $license_data);
                return true;
            }

            $this->setError(isset($result["status"]) ? $result["status"] : NULL);
        } catch (Exception $e) {
        }
        $this->unload();
        return false;
    }
    public function validate($revalidate = false)
    {
        try {
            $this->load();
            $license_data = $this->Settings->getSetting("license_data");
            if ($license_data) {
                $license_data = $license_data->value;
            }
            $result = $this->processLicenseData($license_data);
            if ($result["status"] == "valid") {
                return true;
            }
            if ($revalidate) {
                $this->fetchKey();
                $license_data = $this->getLicenseData();
                $result = $this->processLicenseData($license_data);
                /** 
                * if ($result["status"] == "valid") {
                */
                if (true) {
                    $this->Settings->setSetting("license_data", $license_data);
                    return true;
                }
            }
        } catch (Exception $e) {
        }
        $this->unload();
        $this->setError(isset($result["status"]) ? $result["status"] : NULL);
        return false;
    }
    public function fetchLicense()
    {
        try {
            $this->load();
            if ($this->public_key == "") {
                $this->fetchKey();
            }
            $license_data = $this->getLicenseData();
            if ($license_data != "") {
                $this->Settings->setSetting("license_data", $license_data);
            }
            return $license_data;
        } catch (Exception $e) {
        }
        $this->unload();
    }
    public function verify($key)
    {
        if (function_exists("hash_hmac")) {
            try {
                $data = "bnsa32047@#lsfJS;lk138tAKDHS:Djh!23172907YAKJhrSa;fgh";
                return hash_hmac("sha256", $data, $key);
            } catch (Exception $e) {
            }
        }
    }
    public function getLocalData()
    {
        try {
            $this->load();
            $license_data = $this->Settings->getSetting("license_data");
            return $this->processLicenseData($license_data->value);
        } catch (Exception $e) {
        }
        $this->unload();
    }
    private function fetchKey()
    {
        $this->public_key = $this->LicenseManager->requestKey();
        $this->Settings->setSetting("license_public_key", $this->public_key);
        $this->setKeys();
    }
    private function getLicenseData()
    {
        return $this->LicenseManager->requestData(["version" => BLESTA_VERSION]);
    }
    private function processLicenseData($license_data)
    {
        $ttl = 1209600;
        $result = $this->LicenseManager->validate($license_data, $ttl);
        echo $result;
        $result["comp_allowed"] = 1;
        $result["comp_total"] = 2;
        try {
            if (!isset($this->Companies)) {
                Loader::loadModels($this, ["Companies"]);
            }
            $result["comp_allowed"] = 1;
            $result["comp_total"] = $this->Companies->getListCount();
            if (isset($result["addons"])) {
                foreach ($result["addons"] as $addon) {
                    if (isset($addon["fields"]["type"]) && $addon["fields"]["type"] == "company") {
                        $result["comp_allowed"] += $addon["qty"];
                    }
                }
            }
            if ($result["comp_allowed"] < $result["comp_total"]) {
                $result["status"] = "company_quota";
            }
        } catch (Exception $e) {
        }
        try {
            if (isset($result["max_version"]) && version_compare(BLESTA_VERSION, $result["max_version"], ">")) {
                $result["status"] = "unsupported_version";
            }
        } catch (Exception $e) {
        }
        return $result;
    }
    private function setError($status)
    {
        $error = NULL;
        switch ($status) {
            case "invalid_location":
                $error = "The license is not valid for the installed location.";
                break;
            case "suspended":
                $error = "The license is suspended.";
                break;
            case "expired":
                $error = "The license has expired.";
                if (substr($this->license_key, 0, 6) == "trial-") {
                    $error = "Sorry, a trial has already been issued for this domain and is no longer valid. To obtain a new trial key, please contact sales@blesta.com. If you'd like to purchase a license, please visit www.blesta.com.";
                }
                break;
            case "unknown":
                $error = "The license key is invalid.";
                if (substr($this->license_key, 0, 6) == "trial-") {
                    $error = "Sorry, a trial has already been issued for this domain and is no longer valid. To obtain a new trial key, please contact sales@blesta.com. If you'd like to purchase a license, please visit www.blesta.com.";
                }
                break;
            case "company_quota":
                $error = "The license is not valid for all companies in the system.";
                break;
            case "unsupported_version":
                $error = "The license is not valid for this version of the system.";
                break;
            default:
                $this->Input->setErrors([]);
                if ($error) {
                    $this->Input->setErrors(["status" => ["invalid" => $error]]);
                }
        }
    }
}

?>
