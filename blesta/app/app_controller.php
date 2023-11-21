<?php

if (defined("BLESTA_VERSION")) {
    throw new Exception("Unexpected constant: BLESTA_VERSION defined");
}
define("BLESTA_VERSION", "5.8.2");
class AppController extends Controller
{
    use Blesta\Core\Util\Common\Traits\Container;
    public $request_uri = NULL;
    public $server_protocol = NULL;
    public $base_url = NULL;
    public $base_uri = NULL;
    protected $company_id = NULL;
    protected $public_uri = NULL;
    protected $admin_uri = NULL;
    protected $client_uri = NULL;
    protected $helpers = ["CurrencyFormat", "Date", "DataStructure", "Form", "Html", "Xml", "Javascript", "Widget", "WidgetClient"];
    protected $components = ["Security"];
    protected $layout = "default";
    protected $logger = NULL;
    private $portal = "client";
    private $messages = NULL;
    private $params = NULL;
    private $widgets_state = [];
    public final function __construct($controller, $action, $is_cli)
    {
        if (!defined("VENDORWEBDIR")) {
            define("VENDORWEBDIR", str_replace("/index.php", "", WEBDIR) . "vendors/");
        }
        if (function_exists("date_default_timezone_set")) {
            date_default_timezone_set("UTC");
        }
        $this->controller = $controller;
        $this->action = $action;
        $this->is_cli = $is_cli;
        if (substr($this->controller, 0, 5) == "admin") {
            $this->portal = "admin";
        }
        $this->layout = "default";
        Configure::set("System.default_view", $this->portal . DS . $this->layout);
        Configure::load("blesta");
        $db_info = Configure::get("Blesta.database_info");
        if ((!$db_info || empty($db_info)) && strtolower($this->controller) != "install") {
            $this->redirect(WEBDIR . "install");
        }
        unset($db_info);
        parent::__construct();
        $this->request_uri = isset($_SERVER["REQUEST_URI"]) ? $_SERVER["REQUEST_URI"] : "";
        $this->server_protocol = isset($_SERVER["SERVER_PROTOCOL"]) ? $_SERVER["SERVER_PROTOCOL"] : "HTTP/1.0";
        $this->base_url = "http" . (isset($_SERVER["HTTPS"]) && $_SERVER["HTTPS"] != "off" ? "s" : "") . "://" . (isset($_SERVER["HTTP_HOST"]) ? $_SERVER["HTTP_HOST"] : NULL) . "/";
        $webdir = WEBDIR;
        if ($this->is_cli) {
            $this->uses(["Settings"]);
            $root_web = $this->Settings->getSetting("root_web_dir");
            if ($root_web) {
                $webdir = str_replace(DS, "/", str_replace(rtrim(strtolower($root_web->value), DS), "", strtolower(ROOTWEBDIR)));
                if (!HTACCESS) {
                    $webdir .= "index.php/";
                }
            }
        }
        $this->logger = $this->getFromContainer("logger");
        $this->public_uri = $webdir;
        $this->base_uri = $this->public_uri;
        $this->admin_uri = $this->public_uri . Configure::get("Route.admin") . "/";
        $this->client_uri = $this->public_uri . Configure::get("Route.client") . "/";
        $filtered_uri = Router::filterUri($this->request_uri);
        if (str_starts_with($filtered_uri, Configure::get("Route.admin"))) {
            $this->base_uri = $this->admin_uri;
        } else {
            if (str_starts_with($filtered_uri, Configure::get("Route.client"))) {
                $this->base_uri = $this->client_uri;
            }
        }
        $this->structure->base_uri = $this->base_uri;
        $this->structure->admin_uri = $this->admin_uri;
        $this->structure->client_uri = $this->client_uri;
        $this->structure->base_url = $this->base_url;
        $this->view->base_uri = $this->base_uri;
        $this->view->admin_uri = $this->admin_uri;
        $this->view->client_uri = $this->client_uri;
        $this->view->base_url = $this->base_url;
    }
    protected function getCompany()
    {
        if (!isset($this->Companies)) {
            $this->uses(["Companies"]);
        }
        if (!isset($this->Session)) {
            $this->components(["Session"]);
        }
        $company = false;
        if (isset($this->Session) && 0 < $this->Session->read("blesta_id") && $this->Session->read("blesta_company_id")) {
            $company = $this->Companies->get($this->Session->read("blesta_company_id"));
        } else {
            $company = $this->Companies->getByHostname(isset($_SERVER["SERVER_NAME"]) ? $_SERVER["SERVER_NAME"] : NULL);
            if (!$company) {
                $companies = $this->Companies->getList();
                $company = $companies[0];
                unset($companies);
            }
        }
        return $company;
    }
    protected function primeCompany($company)
    {
        if (!isset($this->Companies)) {
            $this->uses(["Companies"]);
        }
        if (!isset($this->Themes)) {
            $this->uses(["Themes"]);
        }
        if (!isset($this->Form)) {
            $this->helpers(["Form"]);
        }
        if (!isset($this->Session)) {
            $this->components(["Session"]);
        }
        $this->company_id = $company->id;
        Configure::set("Blesta.company", $company);
        Configure::set("Blesta.company_id", $this->company_id);
        Configure::set("Blesta.company_timezone", $this->Companies->getSetting(Configure::get("Blesta.company_id"), "timezone")->value);
        Configure::set("Blesta.language", $this->Companies->getSetting(Configure::get("Blesta.company_id"), "language")->value);
        Language::setLang(Configure::get("Blesta.language"));
        $this->Date->setTimezone("UTC", Configure::get("Blesta.company_timezone"));
        $this->Date->setFormats(["date" => $this->Companies->getSetting(Configure::get("Blesta.company_id"), "date_format")->value, "date_time" => $this->Companies->getSetting(Configure::get("Blesta.company_id"), "datetime_format")->value]);
        $this->CurrencyFormat->setCompany(Configure::get("Blesta.company_id"));
        if ($this->portal == "client") {
            $this->layout = "bootstrap";
            $client_view_dir = $this->Companies->getSetting(Configure::get("Blesta.company_id"), "client_view_dir");
            if ($client_view_dir && is_dir(VIEWDIR . $this->portal . DS . $client_view_dir->value)) {
                $this->layout = $client_view_dir->value;
            }
            $client_view_override = $this->Companies->getSetting(Configure::get("Blesta.company_id"), "client_view_override");
            if (($client_view_override->value ?? "false") == "true") {
                $client_templates = $this->Companies->getViewDirs("client");
                if (isset($this->get["bltemplate"]) && array_key_exists($this->get["bltemplate"], $client_templates)) {
                    $this->layout = trim($this->get["bltemplate"]);
                    $this->Session->write("blesta_template", $this->layout);
                }
            }
            $session_client_template = $this->Session->read("blesta_template");
            if (!empty($session_client_template)) {
                $client_templates = $this->Companies->getViewDirs("client");
                if (array_key_exists($session_client_template, $client_templates)) {
                    $this->layout = $session_client_template;
                }
            }
            if (isset($this->get["bltheme"])) {
                $client_themes = $this->Form->collapseObjectArray($this->Themes->getAll("client", $this->company_id), "name", "id");
                if (array_key_exists($this->get["bltheme"], $client_themes)) {
                    $this->Session->write("blesta_theme", $this->get["bltheme"]);
                }
            }
            $this->setDefaultView($this->portal . DS . $this->layout);
        } else {
            if ($this->portal == "admin" && ($user_id = $this->isLoggedIn())) {
                $this->layout = "default";
                $admin_view_dir = $this->Companies->getSetting(Configure::get("Blesta.company_id"), "admin_view_dir");
                if ($admin_view_dir && is_dir(VIEWDIR . $this->portal . DS . $admin_view_dir->value)) {
                    $this->layout = $admin_view_dir->value;
                }
                $this->setDefaultView($this->portal . DS . $this->layout);
            }
        }
    }
    public function prePartial($view, $params = NULL, $dir = NULL)
    {
        parent::prePartial($view, $params, $dir);
        if (!$this->plugin) {
            $view_file = ROOTWEBDIR . $this->view->view_path . "views" . DS . $this->view->view . DS . $view . $this->view->view_ext;
            if (!file_exists($view_file)) {
                $default_view = Configure::get("Blesta.default_" . $this->portal . "_view_template");
                return ["dir" => $this->portal . DS . $default_view];
            }
        }
        return ["dir" => NULL];
    }
    public function preAction()
    {
        parent::preAction();
        if (strtolower($this->controller) == "admin_login" && ($this->action == "index" || $this->action == "") && !empty($this->post)) {
            $this->licenseCheck();
        } else {
            if (mt_rand(0, 100) <= 2) {
                $this->licenseCheck();
            }
        }
        $this->components(["Session"]);
        $this->uses(["Staff"]);
        $company = $this->getCompany();
        $this->primeCompany($company);
        if (!$this->isStaffAsClient() && $this->portal != "admin" && isset($this->get["lang"])) {
            $this->setClientLanguage($this->get["lang"]);
        }
        $this->setDefaultLanguage();
        $this->triggerPreAction();
        if (isset($this->Session)) {
            Language::loadLang("app_controller");
            $this->Session->keepAliveSessionCookie("/", "", false, true);
            $flash = $this->Session->read("flash");
            if ($flash && !$this->isAjax()) {
                foreach ($flash as $key => $value) {
                    switch ($key) {
                        case "error":
                        case "message":
                        case "notice":
                        case "info":
                            try {
                                $this->setMessage($key, $value, false, $flash, isset($flash["in_current_view"]) ? $flash["in_current_view"] : true);
                            } catch (Exception $e) {
                                $this->setMessage($key, $value, false, $flash, false);
                            }
                            break;
                    }
                }
                $this->Session->clear("flash");
            }
        }
        Language::loadLang("_global");
        Language::loadLang("app_controller");
        if ($this->portal == "admin") {
            $this->structure->set("page_title_lang", Loader::toCamelCase($this->controller) . "." . Loader::fromCamelCase($this->action ? $this->action : "index") . ".page_title");
            $this->structure->set("get_params", $this->get);
        }
        $this->set("system_company", $company);
        $this->structure->set("system_company", $company);
        $this->structure->set("staff_as_client", $this->isStaffAsClient());
        $this->structure->set("logged_in", $this->isLoggedIn());
        $hash_version = $this->Staff->systemHash(BLESTA_VERSION);
        $this->structure->set("hash_version", $hash_version);
        if (!isset($this->PluginManager)) {
            $this->uses(["PluginManager"]);
        }
        if (!isset($this->Actions)) {
            $this->uses(["Actions"]);
        }
        if ($this->portal == "client") {
            $this->structure->set("portal_installed", $this->PluginManager->isInstalled("cms", Configure::get("Blesta.company_id")));
        }
        if ($this->plugin) {
            $result = $this->PluginManager->getByDir($this->plugin, $this->company_id);
            if (isset($result[0])) {
                if (!$result[0]->enabled) {
                    throw new Exception(NULL, 404);
                }
                foreach ($this->Actions->getAll(["plugin_id" => $result[0]->id]) as $action) {
                    if ($action->enabled != 1 && str_contains($this->request_uri, $action->url)) {
                        throw new Exception(NULL, 404);
                    }
                }
            }
        }
        $this->verifyCsrfToken();
        if (!$this->isAjax()) {
            $this->setNav();
            $this->setTheme();
            $is_manager = $this->Session->read("blesta_contact_id");
            $this->structure->set("is_manager", $is_manager);
            if ($is_manager) {
                $this->setManagerBanner();
            }
            if (isset($this->Javascript)) {
                $this->Javascript->setDefaultPath($this->structure->view_dir . "javascript/");
                if ($this->portal == "admin") {
                    $this->Javascript->setFile("jquery.min.js");
                    $this->Javascript->setFile("jquery-migrate.min.js");
                    $this->Javascript->setFile("jquery-carousel-1.0.1.js");
                    $this->Javascript->setFile("jquery-ui-1.10.3.custom.min.js");
                    $this->Javascript->setFile("jquery.qtip.min.js");
                    $this->Javascript->setFile("history/json2.js", "head", NULL, "lt IE 10");
                    $this->Javascript->setFile("history/history.adapter.jquery.js");
                    $this->Javascript->setFile("history/history.js");
                    $this->Javascript->setFile("history/history.html4.js", "head", NULL, "lt IE 10");
                    $this->Javascript->setFile("innershiv.min.js", "head", NULL, "lt IE 9");
                    $this->Javascript->setFile("app.min.js?v=" . $hash_version);
                }
                $this->setDevBanner();
                if ($this->portal == "admin") {
                    $this->setKeyBanner();
                }
            }
            if ($this->portal == "admin") {
                $this->structure->set("system_companies", $this->Companies->getAllAvailable($this->Session->read("blesta_staff_id")));
                $this->structure->set("search_options", $this->Navigation->getSearchOptions($this->base_uri));
                $search_state = $this->Staff->getSetting($this->Session->read("blesta_staff_id"), "search_" . Configure::get("Blesta.company_id") . "_state");
                if ($search_state && $search_state->value != "") {
                    $this->structure->set("default_search_option", $search_state->value);
                }
            } else {
                if (!isset($this->Clients)) {
                    $this->uses(["Clients"]);
                }
                if (!isset($this->Contacts)) {
                    $this->uses(["Contacts"]);
                }
                if (!isset($this->Languages)) {
                    $this->uses(["Languages"]);
                }
                if (!isset($client)) {
                    $client = $this->Clients->get($this->Session->read("blesta_client_id"));
                }
                $has_email_permission = false;
                if ($client) {
                    $this->structure->set("client", $client);
                    $contact = $this->Contacts->getByUserId($this->Session->read("user_id"), $client->id);
                    if ($contact) {
                        $has_email_permission = (bool) $this->Contacts->hasPermission($this->company_id, $contact->id, "client_emails");
                    } else {
                        $has_email_permission = true;
                        $contact = $this->Contacts->get($client->contact_id);
                    }
                    $this->structure->set("contact", $contact);
                }
                $this->structure->set("has_email_permission", $has_email_permission);
                $this->structure->set("languages", $this->Form->collapseObjectArray($this->Languages->getAll(Configure::get("Blesta.company_id")), "name", "code"));
                $company_show_language = $this->Companies->getSetting(Configure::get("Blesta.company_id"), "client_set_lang");
                $this->structure->set("show_language", isset($client->settings["client_set_lang"]) ? $client->settings["client_set_lang"] == "true" : $company_show_language && $company_show_language->value == "true");
                $this->structure->set("request_uri", $this->request_uri);
            }
            $requestor = $this->getFromContainer("requestor");
            $this->structure->set("language", $requestor->language);
        }
        $this->components(["SettingsCollection"]);
        if ($this->portal == "admin") {
            $admin_logo_height = $this->SettingsCollection->fetchSetting(NULL, Configure::get("Blesta.company_id"), "admin_logo_height");
            $this->structure->set("admin_logo_height", $admin_logo_height["value"] ?? NULL);
        }
        if ($this->portal == "client") {
            $client_logo_height = $this->SettingsCollection->fetchSetting(NULL, Configure::get("Blesta.company_id"), "client_logo_height");
            $this->structure->set("client_logo_height", $client_logo_height["value"] ?? NULL);
        }
        if (!$this->plugin) {
            $this->view->default_view = $this->portal . DS . Configure::get("Blesta.default_" . $this->portal . "_view_template");
        }
    }
    private function setManagerBanner()
    {
        $manager_id = $this->Session->read("blesta_contact_id");
        if (!empty($manager_id) && is_numeric($manager_id)) {
            $this->Javascript->setInline("\n                \$(document).ready(function () {\n                    \$(\"body\").append(\"<div style=\\\"all: unset !important;position: fixed !important;display: block !important;bottom: 0 !important;top: unset !important;right: unset !important;left: unset !important;transform: none !important;width: 100% !important;max-width: 100% !important;height: unset !important;max-height: unset !important;padding: 10px 0 !important;margin: 0 !important;text-align: center !important;z-index: 3147483647 !important;opacity: 1 !important;background: none !important;background-color: #d1ecf1 !important;border-top: 1px solid #bee5eb !important;color: #0c5360 !important;\\\"><p style=\\\"all:unset !important;font-size:18px !important;\\\"><i class=\\\"fas fa-fw fa-exclamation-triangle\\\"></i> " . Language::_("AppController.banners.client_manager", true) . " <a href=\\\"" . $this->client_uri . "managers/switch/\\\">" . Language::_("AppController.banners.text_switch_back", true) . "</a>" . "</p>" . "</div>" . "\");\n                });\n            ");
        }
    }
    protected function triggerPreAction()
    {
        $eventFactory = $this->getFromContainer("util.events");
        $eventListener = $eventFactory->listener();
        $eventListener->register("AppController.preAction");
        $eventListener->trigger($eventFactory->event("AppController.preAction"));
        if (!$this->isAjax()) {
            $event = $eventFactory->event("AppController.structure", ["plugin" => $this->plugin, "controller" => $this->controller, "action" => $this->action, "portal" => $this->portal, "get" => $this->get]);
            $keys = ["head", "body_start", "body_end"];
            $event->setReturnValue(array_fill_keys($keys, []));
            $eventListener->register("AppController.structure");
            $event_result = $eventListener->trigger($event)->getReturnValue();
            if (is_array($event_result)) {
                foreach ($event_result as $key => $val) {
                    if (in_array($key, $keys) && is_array($val)) {
                        $this->structure->set($key, implode("\n", $val));
                    } else {
                        $this->structure->set($key, $val);
                    }
                }
            }
            unset($event);
            unset($event_result);
        }
    }
    public function postAction()
    {
        parent::postAction();
        $this->setMaintenance();
        if ($user_id = $this->isLoggedIn()) {
            $this->uses(["Logs"]);
            $requestor = $this->getFromContainer("requestor");
            $ip_address = $requestor->ip_address;
            $company_id = Configure::get("Blesta.company_id");
            if ($this->Logs->validateUserLogExists($user_id, $ip_address, $company_id)) {
                $this->Logs->updateUser($user_id, $ip_address, $company_id);
            } else {
                $this->Logs->addUser(["user_id" => $user_id, "ip_address" => $ip_address, "company_id" => $company_id]);
            }
        }
    }
    protected function setClientLanguage($language_code)
    {
        $this->uses(["Clients", "Companies", "Languages"]);
        $this->components(["Session"]);
        if (!($language = $this->Languages->get(Configure::get("Blesta.company_id"), $language_code))) {
            return false;
        }
        if (($client = $this->Clients->get($this->Session->read("blesta_client_id"))) && isset($client->settings["client_set_lang"]) && $client->settings["client_set_lang"] == "true") {
            $this->Session->write("blesta_language", $language->code);
            if ($client->user_id == $this->Session->read("blesta_id")) {
                $this->Clients->setSetting($client->id, "language", $language->code);
            }
        } else {
            if (($show_lang = $this->Companies->getSetting(Configure::get("Blesta.company_id"), "client_set_lang")) && $show_lang->value == "true") {
                $this->Session->write("blesta_language", $language->code);
            }
        }
    }
    private function setDefaultLanguage()
    {
        $this->uses(["Languages"]);
        if (isset($this->Session)) {
            $language_code = NULL;
            if ($this->portal == "admin" || $this->isStaffAsClient()) {
                $staff_id = $this->Session->read("blesta_staff_id");
                if (!empty($staff_id) && ($language = $this->Staff->getSetting($staff_id, "language", $this->company_id))) {
                    $language_code = $language->value;
                }
            } else {
                if ($this->portal == "client") {
                    if (($temp_lang = $this->Session->read("blesta_language")) && ($lang = $this->Languages->get($this->company_id, $temp_lang))) {
                        $language_code = $lang->code;
                    }
                    if ($language_code === NULL && is_numeric($this->Session->read("blesta_id")) && is_numeric($this->Session->read("blesta_client_id"))) {
                        if (!isset($this->Clients)) {
                            $this->uses(["Clients"]);
                        }
                        if (!isset($this->SettingsCollection)) {
                            $this->components(["SettingsCollection"]);
                        }
                        if ($client = $this->Clients->getByUserId($this->Session->read("blesta_id"))) {
                            $language = $this->SettingsCollection->fetchClientSetting($client->id, NULL, "language");
                            if ($language && array_key_exists("value", $language)) {
                                $language_code = $language["value"];
                            }
                        }
                    }
                }
            }
            if ($language_code !== NULL) {
                Configure::set("Blesta.language", $language_code);
                Language::setLang(Configure::get("Blesta.language"));
            }
        }
    }
    protected function setMaintenance()
    {
        $this->components(["SettingsCollection"]);
        if (!isset($this->Session)) {
            return NULL;
        }
        $system_settings = $this->SettingsCollection->fetchSystemSettings();
        if (isset($system_settings["maintenance_mode"]) && $system_settings["maintenance_mode"] == "true") {
            if (!(0 < $this->Session->read("blesta_staff_id") || str_contains($this->request_uri, Configure::get("Route.admin")))) {
                if (0 < $this->Session->read("blesta_id")) {
                    $this->Session->clear();
                }
                if (!str_contains($this->request_uri, Configure::get("Route.client") . "/maintenance") && !str_contains($this->request_uri, Configure::get("Route.client") . "/theme")) {
                    $this->redirect($this->client_uri . "maintenance/");
                }
            }
            $this->structure->set("maintenance_mode", true);
        }
    }
    private function setDevBanner()
    {
        $this->uses(["Settings"]);
        $license_key = $this->Settings->getSetting("license_key");
        if (substr($license_key->value, 0, 4) === "dev-") {
            $this->Javascript->setInline("\n                \$(document).ready(function () {\n                    \$(\"body\").append(\"<div style=\\\"all: unset !important;position: fixed !important;display: block !important;bottom: 0 !important;top: unset !important;right: unset !important;left: unset !important;transform: none !important;width: 100% !important;max-width: 100% !important;height: unset !important;max-height: unset !important;padding: 10px 0 !important;margin: 0 !important;text-align: center !important;z-index: 2147483647 !important;opacity: 1 !important;background: none !important;background-color: #fffed9 !important;border-top: 1px solid #f5f5f5 !important;color: #4b4b4b !important;\\\"><p style=\\\"all:unset !important;font-size:14px !important;\\\"><i class=\\\"fas fa-fw fa-exclamation-triangle\\\"></i> This installation of Blesta is running under a Developer License and is not permitted to be used in production. Please report any cases of abuse to <a href=\\\"mailto:licensing@blesta.com\\\">licensing@blesta.com</a>.</p></div>\");\n                });\n            ");
        }
    }
    private function setKeyBanner()
    {
        $this->uses(["Settings"]);
        $license_key = $this->Settings->getSetting("system_key_parity_string");
        if (!empty($license_key->value) && $this->Settings->systemDecrypt($license_key->value) !== "I pity the fool that doesn't copy their config file!") {
            $this->Javascript->setInline("\n                \$(document).ready(function () {\n                    \$(\"body\").append(\"<div style=\\\"all: unset !important;position: fixed !important;display: block !important;bottom: 0 !important;top: unset !important;right: unset !important;left: unset !important;transform: none !important;width: 100% !important;max-width: 100% !important;height: unset !important;max-height: unset !important;padding: 10px 0 !important;margin: 0 !important;text-align: center !important;z-index: 2147483647 !important;opacity: 1 !important;background: none !important;background-color: #ffd9d9 !important;border-bottom: 1px solid #f6b9b9 !important;color: #4b4b4b !important;\\\"><p style=\\\"all:unset !important;font-size:14px !important;font-weight:bold !important;\\\"><i class=\\\"fab fa-fw fa-whmcs\\\"></i> Oh No! The system key in /config/blesta.php does not match your database! Until the original system key is restored, Blesta will be unable to decrypt any encrypted values in your database. Do not continue to use Blesta! Restore this key immediately! If you moved Blesta recently, it was moved incorrectly. Please review the moving Blesta docs at <a href=\\\"https://docs.blesta.com/display/user/Moving+Blesta\\\">https://docs.blesta.com/display/user/Moving+Blesta</a>.</p></div>\");\n                \$(\"head\").append(\"<style>#footer {margin-bottom: 60px !important;}</style>\");\n                });\n            ");
        }
    }
    protected function verifyCsrfToken()
    {
        if (!empty($this->post) && Configure::get("Blesta.verify_csrf_token")) {
            $bypass_action = ["reorderwidgets", "togglewidget"];
            $bypass = Configure::get("Blesta.csrf_bypass");
            $csrf_action = strtolower($this->action);
            $csrf_controller = strtolower($this->controller);
            if ($csrf_action == "") {
                $csrf_action = "index";
            }
            if (!in_array(strtolower($csrf_action), $bypass_action) && !in_array($csrf_controller . "::" . $csrf_action, $bypass) && !$this->Form->verifyCsrfToken()) {
                $this->post = [];
                $this->setMessage("error", Language::_("AppController.!error.invalid_csrf", true), false, NULL, false);
            } else {
                unset($this->post["_csrf_token"]);
            }
            unset($bypass_action);
            unset($bypass);
            unset($csrf_controller);
            unset($csrf_action);
        }
    }
    protected function setMessage($type, $value, $return = false, $params = NULL, $in_current_view = true)
    {
        $this->messages[$type] = $value;
        if (!array_key_exists("preserve_tags", (array) $params)) {
            $params["preserve_tags"] = false;
        }
        $this->params = array_merge((array) $this->params, (array) $params);
        if (!$in_current_view) {
            $view_path = $this->view->view_path;
            $default_view_path = $this->view->default_view_path;
            $this->view->setDefaultView(APPDIR);
        }
        $message = $this->partial("message", array_merge($this->messages, $this->params), Configure::get("System.default_view"));
        if (!$in_current_view) {
            $this->view->view_path = $view_path;
            $this->view->default_view_path = $default_view_path;
        }
        if ($return) {
            return $message;
        }
        $this->set("message", $message);
    }
    protected function flashMessage($type, $value, $params = NULL, $in_current_view = true)
    {
        if (isset($this->Session)) {
            $this->Session->write("flash", array_merge([$type => $value], (array) $params, ["in_current_view" => $in_current_view]));
        }
    }
    protected function requireLogin($redirect_to = NULL)
    {
        $ajax = $this->isAjax();
        if ($user_id = $this->isLoggedIn()) {
            if (!$this->authorized()) {
                if ($ajax) {
                    header($this->server_protocol . " 403 Forbidden");
                    exit;
                }
                $this->setMessage("error", Language::_("AppController.!error.unauthorized_access", true), false, NULL, false);
                $this->view->setDefaultView(APPDIR);
                $this->structure->setDefaultView(APPDIR);
                $this->render("unauthorized", Configure::get("System.default_view"));
                exit;
            }
            if ($this->portal == "client") {
                $area = $this->plugin ? $this->plugin . ".*" : $this->controller;
                $this->requirePermission($area);
            }
            return $user_id;
        }
        if (!$ajax) {
            if ($redirect_to == NULL) {
                $redirect_to = $this->base_uri . "login/";
            }
            $this->Session->write("blesta_forward_to", $_SERVER["REQUEST_URI"]);
            $this->redirect($redirect_to);
        }
        header($this->server_protocol . " 401 Unauthorized");
        exit;
    }
    protected function requirePermission($area)
    {
        $allowed = $this->hasPermission($area);
        if (!$allowed) {
            if ($this->isAjax()) {
                header($this->server_protocol . " 403 Forbidden");
                exit;
            }
            $this->setMessage("error", Language::_("AppController.!error.unauthorized_access", true), false, NULL, false);
            $this->view->setDefaultView(APPDIR);
            $this->render("unauthorized", Configure::get("System.default_view"));
            exit;
        }
    }
    protected function hasPermission($area)
    {
        $level = "client";
        if ($this->portal == "admin") {
            $level = "staff";
        }
        if ($level == "client") {
            if (!isset($this->Contacts)) {
                $this->uses(["Contacts"]);
            }
            if (!isset($this->ManagedAccounts)) {
                $this->uses(["ManagedAccounts"]);
            }
            if (!isset($this->Session)) {
                $this->components(["Session"]);
            }
            if ($contact_id = $this->Session->read("blesta_contact_id")) {
                $contact = $this->Contacts->get($contact_id);
                $client_id = $this->Session->read("blesta_client_id");
            } else {
                $contact = $this->Contacts->getByUserId($this->Session->read("blesta_id"), $this->Session->read("blesta_client_id"));
                $client_id = NULL;
            }
            if ($contact) {
                return !is_null($client_id) ? $this->ManagedAccounts->hasPermission(Configure::get("Blesta.company_id"), $contact->id, $area, $client_id) : $this->Contacts->hasPermission(Configure::get("Blesta.company_id"), $contact->id, $area);
            }
            return true;
        }
    }
    protected function isLoggedIn()
    {
        if (isset($this->Session)) {
            $user_id = $this->Session->read("blesta_id");
            if (is_numeric($user_id) && ($this->portal == "admin" && is_numeric($this->Session->read("blesta_staff_id")) || ($this->portal == "client" || $this->portal == "") && is_numeric($this->Session->read("blesta_client_id")))) {
                return $user_id;
            }
        }
    }
    protected function isStaffAsClient()
    {
        if (isset($this->Session) && $this->portal == "client" && 0 < $this->Session->read("blesta_staff_id")) {
            return true;
        }
        return false;
    }
    protected function isAjax()
    {
        if (isset($_SERVER["HTTP_X_REQUESTED_WITH"]) && strtolower($_SERVER["HTTP_X_REQUESTED_WITH"]) == "xmlhttprequest") {
            return true;
        }
        return false;
    }
    protected function renderAjaxWidgetIfAsync($content_only = false)
    {
        if ($this->isAjax()) {
            $this->set("is_ajax", true);
            $this->renderAjaxWidget($this->controller . (!$this->action || $this->action == "index" ? "" : "_" . $this->action), $content_only);
            return false;
        }
        return true;
    }
    protected function renderAjaxWidget($view, $content_only = false)
    {
        $response = new stdClass();
        if ($this->portal == "client") {
            $render_section = "card-content";
        } else {
            $render_section = "content_section";
        }
        if ($content_only === NULL) {
            $render_section = NULL;
        } else {
            if ($content_only) {
                if ($this->portal == "client") {
                    $render_section = "card-content";
                } else {
                    $render_section = "common_box_content";
                }
            }
        }
        $flash = $this->Session->read("flash");
        $message = NULL;
        if ($flash) {
            foreach ($flash as $key => $value) {
                switch ($key) {
                    case "error":
                    case "message":
                    case "notice":
                        $message .= $this->setMessage($key, $value, true, NULL, false);
                        break;
                }
            }
            $this->Session->clear("flash");
        }
        $this->set("render_section", $render_section);
        $response->replacer = $render_section ? "." . $render_section : NULL;
        $response->content = $this->view->fetch($view);
        $response->message = $message;
        $this->outputAsJson($response);
    }
    protected function outputAsJson($data)
    {
        header("Content-Type: application/json");
        echo json_encode($data);
    }
    protected function getMonths()
    {
        $months = [];
        $abbr_months = [];
        foreach (["jan", "feb", "mar", "apr", "may", "jun", "jul", "aug", "sep", "oct", "nov", "dec"] as $month) {
            $months[] = Language::_("AppController.dates.month_" . $month, true);
            $abbr_months[] = Language::_("AppController.dates.monthabbr_" . $month, true);
        }
        return ["months" => $months, "abbr_months" => $abbr_months];
    }
    protected function getDaysOfWeek()
    {
        $this->components(["SettingsCollection"]);
        $calendar_begins = $this->SettingsCollection->fetchSetting(NULL, $this->company_id, "calendar_begins");
        $start_day = 0;
        if (isset($calendar_begins["value"])) {
            switch ($calendar_begins["value"]) {
                case "monday":
                    $start_day = 1;
                    break;
                case "sunday":
            }
        }
        $days_of_the_week = ["sun", "mon", "tue", "wed", "thur", "fri", "sat"];
        $days = [];
        $abbr_days = [];
        foreach ($days_of_the_week as $day) {
            $days[] = Language::_("AppController.dates.day_" . $day, true);
            $abbr_days[] = Language::_("AppController.dates.dayabbr_" . $day, true);
        }
        return ["days" => $days, "abbr_days" => $abbr_days, "calendar_begins" => $start_day];
    }
    protected function getTimes($interval = 1)
    {
        $times = [];
        $interval = abs($interval);
        if ($interval) {
            for ($i = 0; $i < 24; $i++) {
                $j = 0;
                while ($j < 60) {
                    $time = str_pad($i, 2, 0, STR_PAD_LEFT) . ":" . str_pad($j, 2, 0, STR_PAD_LEFT) . ":00";
                    $times[$time] = $time;
                    $j += $interval;
                }
            }
        }
        return $times;
    }
    public function reorderWidgets()
    {
        $widget_location = NULL;
        switch ($this->controller) {
            case "admin_clients":
                $widget_location = "widget_staff_client";
                break;
            case "admin_main":
                $widget_location = "widget_staff_home";
                break;
            case "admin_billing":
                $widget_location = "widget_staff_billing";
                break;
            default:
                if (!isset($this->Staff)) {
                    $this->uses(["Staff"]);
                }
                $this->setWidgetState($widget_location);
                if (isset($this->post["widget"]) && is_array($this->post["widget"])) {
                    foreach ($this->post["widget"] as $section => $widgets) {
                        foreach ($widgets as $widget_id => $widget) {
                            if (empty($this->post["widget"][$section][$widget_id])) {
                                unset($this->post["widget"][$section][$widget_id]);
                            }
                        }
                        $this->post["widget"][$section] = array_values($this->post["widget"][$section]);
                    }
                    foreach ($this->post["widget"] as $section => $values) {
                        if (is_array($values)) {
                            $i = 0;
                            for ($total = count($values); $i < $total; $i++) {
                                $key = $this->PluginManager->systemHash($values[$i]);
                                $widget_state = isset($this->widgets_state[$key]) ? $this->widgets_state[$key] : [];
                                $widget_state["section"] = $section;
                                unset($this->widgets_state[$key]);
                                $this->widgets_state[$key] = $widget_state;
                            }
                        }
                    }
                    if ($widget_location == "widget_staff_home") {
                        $this->Staff->saveHomeWidgetsState($this->Session->read("blesta_staff_id"), $this->company_id, $this->widgets_state);
                    } else {
                        if ($widget_location == "widget_staff_client") {
                            $this->Staff->saveClientsWidgetsState($this->Session->read("blesta_staff_id"), $this->company_id, $this->widgets_state);
                        } else {
                            if ($widget_location == "widget_staff_billing") {
                                $this->Staff->saveBillingWidgetsState($this->Session->read("blesta_staff_id"), $this->company_id, $this->widgets_state);
                            }
                        }
                    }
                }
                return false;
        }
    }
    public function toggleWidget()
    {
        $widget_location = NULL;
        switch ($this->controller) {
            case "admin_clients":
                $widget_location = "widget_staff_client";
                break;
            case "admin_main":
                $widget_location = "widget_staff_home";
                break;
            case "admin_billing":
                $widget_location = "widget_staff_billing";
                break;
            default:
                if (!isset($this->Staff)) {
                    $this->uses(["Staff"]);
                }
                if (!empty($this->post)) {
                    $this->setWidgetState($widget_location);
                    foreach ($this->post as $type => $value) {
                        $this->widgets_state[$this->Staff->systemHash($type)]["open"] = $value == "false" ? false : true;
                    }
                    if ($widget_location == "widget_staff_home") {
                        $this->Staff->saveHomeWidgetsState($this->Session->read("blesta_staff_id"), $this->company_id, $this->widgets_state);
                    } else {
                        if ($widget_location == "widget_staff_client") {
                            $this->Staff->saveClientsWidgetsState($this->Session->read("blesta_staff_id"), $this->company_id, $this->widgets_state);
                        } else {
                            if ($widget_location == "widget_staff_billing") {
                                $this->Staff->saveBillingWidgetsState($this->Session->read("blesta_staff_id"), $this->company_id, $this->widgets_state);
                            }
                        }
                    }
                }
                return false;
        }
    }
    public function getWidgets()
    {
        if (!isset($this->PluginManager)) {
            $this->uses(["PluginManager"]);
        }
        if ($this->portal == "admin") {
            $this->adminWidgets();
        } else {
            if ($this->portal == "client") {
                $this->clientWidgets();
            }
        }
        return false;
    }
    protected function adminWidgets()
    {
        if (!isset($this->Staff)) {
            $this->uses(["Staff"]);
        }
        $widgets = [];
        $widget_location = NULL;
        $layout_setting = NULL;
        switch ($this->controller) {
            case "admin_clients":
                $widget_location = "widget_staff_client";
                $layout_setting = "client_layout";
                $client_id = isset($this->get[0]) ? $this->get[0] : NULL;
                $widgets = [$this->PluginManager->systemHash("admin_clients_invoices") => ["uri" => $this->base_uri . "clients/invoices/" . $client_id . "/?whole_widget=true"], $this->PluginManager->systemHash("admin_clients_quotations") => ["uri" => $this->base_uri . "clients/quotations/" . $client_id . "/?whole_widget=true"], $this->PluginManager->systemHash("admin_clients_services") => ["uri" => $this->base_uri . "clients/services/" . $client_id . "/?whole_widget=true"], $this->PluginManager->systemHash("admin_clients_transactions") => ["uri" => $this->base_uri . "clients/transactions/" . $client_id . "/?whole_widget=true"]];
                break;
            case "admin_main":
                $widget_location = "widget_staff_home";
                $layout_setting = "dashboard_layout";
                break;
            case "admin_billing":
                $widget_location = "widget_staff_billing";
                $layout_setting = "billing_layout";
                break;
            default:
                $layout = $this->Staff->getSetting($this->Session->read("blesta_staff_id"), $layout_setting, $this->company_id);
                $layout = $layout ? $layout->value : "layout1";
                $num_sections = $this->layoutSections($layout);
                if (!isset($this->get["section"])) {
                    return false;
                }
                $section = $this->get["section"];
                $plugin_actions = $this->PluginManager->getActions($this->company_id, $widget_location, true);
                foreach ($plugin_actions as $plugin) {
                    $key = $this->PluginManager->systemHash(str_replace(["/", "?", "=", "&", "#"], "_", trim($plugin->uri, "/")));
                    $plugin_query = parse_url($plugin->uri, PHP_URL_QUERY);
                    $plugin_query = $plugin_query ? "?" . $plugin_query : "";
                    $fragment = parse_url($plugin->uri, PHP_URL_FRAGMENT);
                    $fragment = $fragment ? "#" . $fragment : "";
                    $plugin_uri = str_replace($fragment, "", str_replace($plugin_query, "", $plugin->uri));
                    $query = $plugin_query . ($widget_location == "widget_staff_client" ? ($plugin_query ? "&" : "?") . "client_id=" . $client_id : NULL);
                    $widgets[$key] = ["uri" => $this->base_uri . $plugin_uri . $query . $fragment];
                }
                $ordered_widgets = [];
                $this->setWidgetState($widget_location);
                foreach ((array) $this->widgets_state as $key => $state) {
                    if (isset($widgets[$key]) && !(isset($state["disabled"]) && $state["disabled"])) {
                        if (!isset($state["section"])) {
                            $state["section"] = "section1";
                        }
                        if ($state["section"] == $section || $num_sections <= substr($section, -1) && $num_sections <= substr($state["section"], -1)) {
                            $ordered_widgets[$key] = $widgets[$key];
                            foreach ($state as $index => $value) {
                                $ordered_widgets[$key][$index] = $value;
                            }
                        }
                    }
                }
                if ($section == "section1" && $this->controller == "admin_clients") {
                    foreach ((array) $widgets as $key => $info) {
                        if (!isset($this->widgets_state[$key])) {
                            $ordered_widgets[$key] = $widgets[$key];
                            $ordered_widgets[$key]["open"] = true;
                            $ordered_widgets[$key]["section"] = "section1";
                        }
                    }
                }
                $this->outputAsJson($ordered_widgets);
        }
    }
    protected function clientWidgets()
    {
        $widgets = [];
        $widget_location = NULL;
        switch ($this->controller) {
            case "client_main":
                $widget_location = "widget_client_home";
                if (!isset($this->Record)) {
                    $this->components(["Record"]);
                }
                if (!isset($this->Actions)) {
                    Loader::loadModels($this, ["Actions"]);
                }
                $system_widgets = $this->Actions->getAll(["location" => "widget_client_home", "plugin_id" => NULL, "company_id" => $this->company_id, "enabled" => 1]);
                foreach ($system_widgets as $system_widget) {
                    $key = $system_widget->location . "_" . str_replace(["/", "?", "=", "&", "#"], "_", trim(preg_replace("/\\?.*/", "", $system_widget->url), "/"));
                    $widgets[$key] = ["uri" => $this->base_uri . $system_widget->url];
                }
                break;
            default:
                if (!isset($this->get["section"])) {
                    return false;
                }
                $section = $this->get["section"];
                $plugin_actions = $this->PluginManager->getActions($this->company_id, $widget_location, true);
                foreach ($plugin_actions as $plugin) {
                    $key = $plugin->location . "_" . str_replace(["/", "?", "=", "&", "#"], "_", trim(preg_replace("/\\?.*/", "", $plugin->url), "/"));
                    $widgets[$key] = ["uri" => $this->base_uri . $plugin->uri];
                }
                $widgets_order = $this->Companies->getSetting($this->company_id, "layout_widgets_order");
                $widgets_order = isset($widgets_order->value) ? unserialize(base64_decode($widgets_order->value)) : [];
                $widgets = array_merge(array_flip($widgets_order), $widgets);
                foreach ($widgets as $key => $value) {
                    if (!is_array($value)) {
                        unset($widgets[$key]);
                    }
                }
                $this->outputAsJson($widgets);
        }
    }
    public function getCards()
    {
        if (!isset($this->PluginManager)) {
            $this->uses(["PluginManager"]);
        }
        if (!isset($this->Clients)) {
            $this->uses(["Clients"]);
        }
        if (!isset($this->Plugins)) {
            $this->components(["Plugins"]);
        }
        if (!isset($this->get[0]) || !($client = $this->Clients->get($this->get[0]))) {
            return false;
        }
        if ($this->portal == "admin") {
            $this->adminCards();
        } else {
            if ($this->portal == "client") {
                $this->clientCards();
            }
        }
        return false;
    }
    protected function adminCards()
    {
        $client_id = $this->get[0];
        $cards = [];
        $plugin_cards = $this->PluginManager->getCards($this->company_id, "staff", true);
        foreach ($plugin_cards as $plugin_card) {
            $plugin = $this->Plugins->create($plugin_card->plugin_dir);
            $callback = $plugin_card->callback;
            $plugin_card->card_id = $plugin_card->plugin_dir . "_" . Loader::fromCamelCase($plugin_card->callback[1]);
            if (is_array($callback) && isset($callback[1]) && $callback[0] == "this") {
                $callback[0] = $plugin;
            }
            $plugin_card->uri = NULL;
            if (!empty($plugin_card->link)) {
                if (substr($plugin_card->link, 0, 4) == "http") {
                    $plugin_card->uri = $plugin_card->link;
                }
                if (substr($plugin_card->link, 0, 1) == "/") {
                    $plugin_card->uri = WEBDIR . trim($plugin_card->link, "/");
                }
                if (substr($plugin_card->link, 0, 1) !== "/") {
                    $plugin_card->uri = $this->public_uri . Configure::get("Route.admin") . "/" . $plugin_card->link;
                }
            }
            $cards[$plugin_card->card_id] = ["uri" => $plugin_card->uri];
            $plugin_card->value = call_user_func($callback, $client_id);
            $cards[$plugin_card->card_id]["value"] = $this->partial("admin_clients_card", ["card" => $plugin_card, "num_cards" => count($plugin_cards)]);
        }
        $cards_order = $this->Companies->getSetting($this->company_id, "layout_cards_order");
        $cards_order = isset($cards_order->value) ? unserialize(base64_decode($cards_order->value)) : [];
        $cards = array_merge(array_flip($cards_order), $cards);
        foreach ($cards as $key => $value) {
            if (!is_array($value)) {
                unset($cards[$key]);
            }
        }
        $this->outputAsJson($cards);
    }
    protected function clientCards()
    {
        $client_id = $this->get[0];
        $cards = [];
        $plugin_cards = $this->PluginManager->getCards($this->company_id, "client", true);
        foreach ($plugin_cards as $plugin_card) {
            $plugin = $this->Plugins->create($plugin_card->plugin_dir);
            $callback = $plugin_card->callback;
            $plugin_card->card_id = $plugin_card->plugin_dir . "_" . Loader::fromCamelCase($plugin_card->callback[1]);
            if (is_array($callback) && isset($callback[1]) && $callback[0] == "this") {
                $callback[0] = $plugin;
            }
            $plugin_card->uri = NULL;
            if (!empty($plugin_card->link)) {
                if (substr($plugin_card->link, 0, 4) == "http") {
                    $plugin_card->uri = $plugin_card->link;
                }
                if (substr($plugin_card->link, 0, 1) == "/") {
                    $plugin_card->uri = WEBDIR . trim($plugin_card->link, "/");
                }
                if (substr($plugin_card->link, 0, 1) !== "/") {
                    $plugin_card->uri = $this->public_uri . Configure::get("Route.client") . "/" . $plugin_card->link;
                }
            }
            $cards[$plugin_card->card_id] = ["uri" => $plugin_card->uri];
            $plugin_card->value = call_user_func($callback, $client_id);
            $cards[$plugin_card->card_id]["value"] = $this->partial("client_main_card", ["card" => $plugin_card, "num_cards" => count($plugin_cards)]);
        }
        $cards_order = $this->Companies->getSetting($this->company_id, "layout_cards_order");
        $cards_order = isset($cards_order->value) ? unserialize(base64_decode($cards_order->value)) : [];
        $cards = array_merge(array_flip($cards_order), $cards);
        foreach ($cards as $key => $value) {
            if (!is_array($value)) {
                unset($cards[$key]);
            }
        }
        $this->outputAsJson($cards);
    }
    protected function setWidgetState($widget_location)
    {
        if (!isset($this->Staff)) {
            $this->uses(["Staff"]);
        }
        if ($widget_location == "widget_staff_home") {
            $this->widgets_state = $this->Staff->getHomeWidgetsState($this->Session->read("blesta_staff_id"), $this->company_id);
        } else {
            if ($widget_location == "widget_staff_client") {
                $this->widgets_state = $this->Staff->getClientsWidgetsState($this->Session->read("blesta_staff_id"), $this->company_id);
            } else {
                if ($widget_location == "widget_staff_billing") {
                    $this->widgets_state = $this->Staff->getBillingWidgetsState($this->Session->read("blesta_staff_id"), $this->company_id);
                }
            }
        }
    }
    protected function layoutSections($layout)
    {
        switch ($layout) {
            case "layout1":
            case "layout4":
            case "layout2":
                return 1;
                break;
            case "layout3":
                return 2;
                break;
            default:
                return 3;
        }
    }
    protected final function licenseCheck()
    {
        $this->uses(["License"]);
        $key = md5(mt_rand());
        $data = "bnsa32047@#lsfJS;lk138tAKDHS:Djh!23172907YAKJhrSa;fgh";
        $hash = hash_hmac("sha256", $data, $key);
        if ($this->License->verify($key) != $hash) {
            exit("License model invalid.");
        }
        if ($this->portal == "admin" && strtolower($this->controller) != "admin_license" && !(strtolower($this->controller) == "admin_login" && $this->action == "setup") && !$this->License->validate(true)) {
            if (strtolower($this->controller) != "admin_login") {
                $this->redirect($this->base_uri . "logout/");
            }
            $this->redirect($this->base_uri . "license/");
        }
    }
    protected function setNav()
    {
        if (!isset($this->Session)) {
            $this->components(["Session"]);
        }
        $nav = [];
        $this->uses(["Navigation"]);
        $this->Navigation->baseUri("public", $this->public_uri)->baseUri("client", $this->client_uri)->baseUri("admin", $this->admin_uri);
        if ($this->portal == "admin") {
            if (0 < $this->Session->read("blesta_id")) {
                if (!isset($this->Staff)) {
                    $this->uses(["Staff"]);
                }
                $staff_quicklinks = $this->Staff->getQuickLinks($this->Session->read("blesta_staff_id"), $this->company_id);
                $quicklink_active = false;
                foreach ($staff_quicklinks as $quicklink) {
                    if ($quicklink->uri == $this->request_uri) {
                        $quicklink_active = true;
                        $this->structure->set("quicklink_active", $quicklink_active);
                        if (!isset($this->StaffGroups)) {
                            $this->uses(["StaffGroups"]);
                        }
                        $group = $this->StaffGroups->getStaffGroupByStaff($this->Session->read("blesta_staff_id"), $this->company_id);
                        if ($group) {
                            $cache = Cache::fetchCache("nav_staff_group_" . $group->id, $this->company_id . DS . "nav" . DS . $this->Session->read("blesta_staff_id") . DS);
                            if ($cache) {
                                $nav = $this->setNavActive(unserialize(base64_decode($cache)), true, $group);
                            } else {
                                $raw_nav = $this->Navigation->getPrimary($this->admin_uri);
                                if (Configure::get("Caching.on") && is_writable(CACHEDIR)) {
                                    try {
                                        Cache::writeCache("nav_staff_group_" . $group->id, base64_encode(serialize($raw_nav)), strtotime(Configure::get("Blesta.cache_length")) - time(), $this->company_id . DS . "nav" . DS . $this->Session->read("blesta_staff_id") . DS);
                                    } catch (Exception $e) {
                                        Configure::set("Caching.on", false);
                                    }
                                }
                                $nav = $this->setNavActive($raw_nav, false, $group);
                            }
                        }
                    }
                }
            }
        } else {
            if ($this->portal == "client") {
                if ($this->isLoggedIn()) {
                    $nav = $this->setNavActive($this->Navigation->getPrimaryClient($this->client_uri));
                } else {
                    $nav = $this->setNavActive($this->Navigation->getPrimaryPublic($this->public_uri, $this->client_uri));
                }
            }
        }
        $this->structure->set("nav", $nav);
    }
    protected function setNavActive($nav, $is_cached = false, $group = NULL)
    {
        foreach ($nav as $parent_uri => &$data) {
            if (isset($data["route"])) {
                $parent_route = $data["route"];
                if (preg_match("/" . $parent_route["controller"] . "/i", $this->controller)) {
                    $parent_route["controller"] = $this->controller;
                }
            } else {
                $parent_route = Router::routesTo($parent_uri);
            }
            if (isset($parent_route["plugin"])) {
                $parent_route["controller"] = $parent_route["plugin"] . "." . $parent_route["controller"];
            }
            if (!$is_cached && !$this->authorized($parent_route["controller"], "*", $group)) {
                unset($nav[$parent_uri]);
            } else {
                $data["active"] = false;
                if ($this->plugin) {
                    if (isset($parent_route["plugin"]) && $parent_route["plugin"] == $this->plugin && in_array($this->controller, $parent_route["uri"])) {
                        $data["active"] = true;
                    }
                } else {
                    if ($parent_route["controller"] == $this->controller) {
                        $data["active"] = true;
                    }
                }
                $keys = ["sub", "secondary"];
                foreach ($keys as $key) {
                    if (isset($data[$key]) && ($key == "sub" || $data["active"])) {
                        $active_sub_uris = [];
                        $active_sub_uri = NULL;
                        foreach ($data[$key] as $sub_uri => &$sub_data) {
                            if (isset($sub_data["route"])) {
                                $sub_route = $sub_data["route"];
                                if (preg_match("/" . $sub_route["controller"] . "/i", $this->controller)) {
                                    $sub_route["controller"] = $this->controller;
                                }
                            } else {
                                $sub_route = Router::routesTo($sub_uri);
                            }
                            if (isset($sub_route["plugin"])) {
                                $sub_route["controller"] = $sub_route["plugin"] . "." . $sub_route["controller"];
                            }
                            if (!$is_cached && !$this->authorized($sub_route["controller"], $sub_route["action"], $group)) {
                                unset($data[$key][$sub_uri]);
                            } else {
                                $sub_data["active"] = false;
                                $active_sub_uri = $sub_route["action"] == $this->action ? $sub_uri : $active_sub_uri;
                                if ($this->plugin) {
                                    if ($this->plugin . "." . $this->controller == $sub_route["controller"] && ($sub_route["action"] == NULL || $sub_route["action"] == "*" || $sub_route["action"] == $this->action)) {
                                        $data["active"] = true;
                                        $sub_data["active"] = true;
                                        $active_sub_uris[] = $sub_uri;
                                    }
                                } else {
                                    if ($sub_route["controller"] == $this->controller && ($sub_route["action"] == NULL || $sub_route["action"] == "*" || $sub_route["action"] == $this->action)) {
                                        $data["active"] = true;
                                        $sub_data["active"] = true;
                                        $active_sub_uris[] = $sub_uri;
                                    }
                                }
                            }
                        }
                        if ($active_sub_uri != NULL && 1 < count($active_sub_uris)) {
                            foreach ($active_sub_uris as $sub_uri) {
                                if ($sub_uri != $active_sub_uri) {
                                    $data[$key][$sub_uri]["active"] = false;
                                }
                            }
                        }
                    }
                }
                if ($data["active"]) {
                    return $nav;
                }
            }
        }
    }
    protected function setTheme()
    {
        if (!isset($this->Session)) {
            $this->components(["Session"]);
        }
        $dir = "";
        if ($this->controller == "admin_login") {
            $dir = "admin_login";
        }
        if (Configure::get("Blesta.company_id")) {
            $this->components(["SettingsCollection"]);
            $this->helpers(["Color"]);
            $this->uses(["Themes"]);
            $theme_key = "theme_" . $this->portal;
            $blesta_logo = $this->structure->view_dir . "images/logo.svg";
            $theme_id = $this->SettingsCollection->fetchSetting(NULL, Configure::get("Blesta.company_id"), $theme_key);
            $blesta_theme = $this->Session->read("blesta_theme");
            if (!empty($blesta_theme) && $theme_key == "theme_client") {
                $theme_id["value"] = $blesta_theme;
            }
            if (!empty($theme_id["value"])) {
                $theme_id = $theme_id["value"];
                $theme = $this->Themes->get($theme_id);
                if ($theme && in_array($this->portal, ["client", "admin"]) && property_exists($theme, "colors") && !empty($theme->colors["theme_header_bg_color_top"])) {
                    $color = $this->Color->hex($theme->colors["theme_header_bg_color_top"])->contrast50()->asHex();
                    if ($color == "000000") {
                        $blesta_logo = $this->structure->view_dir . "images/logo-color.svg";
                    }
                }
            }
            unset($this->Color);
            $base_uri = $this->base_uri;
            if ($this->portal == "admin") {
                $base_uri = $this->admin_uri;
            } else {
                if ($this->portal == "client") {
                    $base_uri = $this->client_uri;
                }
            }
            $theme_logo = $this->SettingsCollection->fetchSetting(NULL, Configure::get("Blesta.company_id"), "logo_" . $this->portal);
            if (!empty($theme_logo["value"])) {
                $theme_logo = $theme_logo["value"];
            } else {
                $theme_logo = isset($theme->logo_url) ? $theme->logo_url : NULL;
            }
            $this->structure->set("theme_css", $base_uri . "theme/theme.css?dir=" . $dir);
            $this->structure->set("blesta_logo", $blesta_logo);
            $this->structure->set("theme_logo", $theme_logo);
        }
    }
    protected function authorized($controller = NULL, $action = NULL, $group = NULL)
    {
        if (!isset($this->Session)) {
            $this->components(["Session"]);
        }
        $prefix = NULL;
        if ($this->plugin && $controller === NULL) {
            $prefix = $this->plugin . ".";
        }
        $controller = $prefix . ($controller === NULL ? $this->controller : $controller);
        $action = $action === NULL ? $this->action : $action;
        if (!empty($this->plugin)) {
            Loader::loadModels($this, ["PluginManager"]);
            if (!$this->PluginManager->isInstalled($this->plugin, $this->company_id)) {
                return false;
            }
        }
        $aro = NULL;
        if (0 < $this->Session->read("blesta_staff_id")) {
            if ($group === NULL) {
                if (!isset($this->StaffGroups)) {
                    $this->uses(["StaffGroups"]);
                }
                $group = $this->StaffGroups->getStaffGroupByStaff($this->Session->read("blesta_staff_id"), $this->company_id);
            }
            if ($group) {
                $aro = "staff_group_" . $group->id;
                $ip = $this->Session->read("ip");
                if ($group->session_lock && $ip && ($requestor = $this->getFromContainer("requestor")) && $ip != $requestor->ip_address) {
                    if (!isset($this->Users)) {
                        $this->uses(["Users"]);
                    }
                    $this->Users->logout($this->Session);
                    return $this->redirect($this->base_uri);
                }
            }
            if (!isset($this->Staff)) {
                $this->uses(["Staff"]);
            }
            $staff = $this->Staff->get($this->Session->read("blesta_staff_id"));
            if ($staff->status != "active") {
                $this->Session->clear();
                return false;
            }
        }
        if (!isset($this->Permissions)) {
            $this->uses(["Permissions"]);
        }
        $level = "client";
        if ($this->portal == "admin") {
            $level = "staff";
        }
        return $this->Permissions->authorized($aro, $controller, $action, $level, $this->company_id);
    }
    protected function setDefaultView($dir)
    {
        Configure::set("System.default_view", $dir);
        $this->structure->setView(NULL, $dir);
        $this->view->setView(NULL, $dir);
    }
    protected function setPagination($get = [], $settings = [], $ajax = true)
    {
        $this->Pagination = $this->getFromContainer("pagination");
        $this->Pagination->setGet($get);
        $this->Pagination->setSettings($settings);
        if ($ajax) {
            $this->Pagination->setSettings(Configure::get("Blesta.pagination_ajax"));
        }
        $this->view->Pagination = $this->Pagination;
        $this->structure->Pagination = $this->Pagination;
    }
}
include_once dirname(__FILE__) . DS . "admin_controller.php";
include_once dirname(__FILE__) . DS . "client_controller.php";

?>
