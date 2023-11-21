<?php
namespace Blesta\Module\BlestaLicense;

use Blesta\ResellerApi\Connection;
use Blesta\Module\BlestaLicense\Component\ComponentFactory;
use Module as BlestaModule;
use Loader;
use Language;

/**
 * Blesta License Module
 */
class Module extends BlestaModule
{
    /**
     * {@inheritdoc}
     */
    public function getLogo()
    {
        return "src" . DIRECTORY_SEPARATOR . "views" . DIRECTORY_SEPARATOR . "default" .
            DIRECTORY_SEPARATOR . "images" . DIRECTORY_SEPARATOR . "logo.png";
    }

    /**
     * {@inheritdoc}
     */
    public function addService(
        $package,
        array $vars = null,
        $parent_package = null,
        $parent_service = null,
        $status = "pending"
    ) {
        $license = null;

        // Only provision the service if 'use_module' is true
        if (isset($vars['use_module']) && $vars['use_module'] == "true") {
            $result = $this->getComponent('Service')->add(
                $package,
                $vars,
                $parent_package,
                $parent_service,
                $status
            );

            if (($errors = $result->errors())) {
                $this->Input->setErrors($errors);
                return;
            }

            $license = $result->response();
        }

        return array(
            array(
                'key' => 'license',
                'value' => $license
            )
        );
    }

    /**
     * {@inheritdoc}
     */
    public function editService(
        $package,
        $service,
        array $vars = array(),
        $parent_package = null,
        $parent_service = null
    ) {
        // Only provision the service changes if 'use_module' is true
        if (isset($vars['use_module']) && $vars['use_module'] == "true") {
            $result = $this->getComponent('Service')->edit(
                $package,
                $service,
                $vars,
                $parent_package,
                $parent_service
            );

            if (($errors = $result->errors())) {
                $this->Input->setErrors($errors);
                return;
            }
        }

        $service_fields = $this->serviceFieldsToObject($service->fields);

        return array(
            array(
                'key' => 'license',
                'value' => $service_fields->license
            )
        );
    }

    /**
     * {@inheritdoc}
     */
    public function cancelService(
        $package,
        $service,
        $parent_package = null,
        $parent_service = null
    ) {
        $result = $this->getComponent('Service')->cancel(
            $package,
            $service,
            $parent_package,
            $parent_service
        );

        if (($errors = $result->errors())) {
            $this->Input->setErrors($errors);
            return;
        }

        return null;
    }

    /**
     * {@inheritdoc}
     */
    public function suspendService(
        $package,
        $service,
        $parent_package = null,
        $parent_service = null
    ) {
        $result = $this->getComponent('Service')->suspend(
            $package,
            $service,
            $parent_package,
            $parent_service
        );

        if (($errors = $result->errors())) {
            $this->Input->setErrors($errors);
            return;
        }

        return null;
    }

    /**
     * {@inheritdoc}
     */
    public function unsuspendService(
        $package,
        $service,
        $parent_package = null,
        $parent_service = null
    ) {
        $result = $this->getComponent('Service')->unsuspend(
            $package,
            $service,
            $parent_package,
            $parent_service
        );

        if (($errors = $result->errors())) {
            $this->Input->setErrors($errors);
            return;
        }

        return null;
    }

    /**
     * {@inheritdoc}
     */
    public function getPackageFields($vars = null)
    {
        $Package = $this->getComponent('Package');
        $Package->Html = $this->Html;

        return $Package->fields($vars);
    }

    /**
     * {@inheritdoc}
     */
    public function addPackage(array $vars = null)
    {
        $Package = $this->getComponent('Package');
        $meta = $Package->add($vars);

        if (($errors = $Package->errors())) {
            $this->Input->setErrors($errors);
        } else {
            return $meta;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function editPackage($package, array $vars = null)
    {
        // Same as adding
        return $this->addPackage($vars);
    }

    /**
     * {@inheritdoc}
     */
    public function getEmailTags()
    {
        return array(
            'module' => array(),
            'package' => array('name'),
            'service' => array('license')
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getAdminTabs($package)
    {
        return array(
            'tabReissue' => Language::_("BlestaLicense.tab_reissue", true),
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getClientTabs($package)
    {
        return array(
            'tabClientReissue' => array(
                'name' => Language::_("BlestaLicense.tab_reissue", true),
                'icon' => 'fas fa-cog'
            ),
        );
    }

    /**
     * Admin tab to reissue the license
     *
     * @param stdClass $package A stdClass object representing the current package
     * @param stdClass $service A stdClass object representing the current service
     * @param array $get Any GET parameters
     * @param array $post Any POST parameters
     * @param array $files Any FILES parameters
     * @return string The string representing the contents of this tab
     */
    public function tabReissue($package, $service, array $get = null, array $post = null, array $files = null)
    {
        $view = $this->getView('tab_reissue');
        Loader::loadHelpers($view, array('Form', 'Html'));

        if (!empty($post['reissue'])) {
            $this->reissueLicense($package, $service, $post);
        }

        $view->set("client_id", $service->client_id);
        $view->set("service_id", $service->id);
        return $view->fetch();
    }

    /**
     * Client tab to reissue the license
     *
     * @param stdClass $package A stdClass object representing the current package
     * @param stdClass $service A stdClass object representing the current service
     * @param array $get Any GET parameters
     * @param array $post Any POST parameters
     * @param array $files Any FILES parameters
     * @return string The string representing the contents of this tab
     */
    public function tabClientReissue($package, $service, array $get = null, array $post = null, array $files = null)
    {
        $view = $this->getView('tab_client_reissue');
        Loader::loadHelpers($view, array('Form', 'Html'));

        if (!empty($post['reissue'])) {
            $this->reissueLicense($package, $service, $post);
        }

        $view->set("service_id", $service->id);
        return $view->fetch();
    }

    /**
     * Reissues the license
     *
     * @param stdClass $package A stdClass object representing the current package
     * @param stdClass $service A stdClass object representing the current service
     * @param array $data An array containing:
     *  - reissue "true" to reissue the license
     */
    private function reissueLicense($package, $service, array $data)
    {
        $result = $this->getComponent('Service')->edit($package, $service, $data);

        if (($errors = $result->errors())) {
            $this->Input->setErrors($errors);
        }
    }

    /**
     * Initialize an API connection
     *
     * @param array $moduleRow
     * @return Connection
     */
    private function getConnection(array $moduleRow = null)
    {
        if (null === $moduleRow) {
            $moduleRow = $this->getModuleRow();
        }

        // Fetch the first module row if one does not exist
        if (null === $moduleRow) {
            $rows = $this->getModuleRows();
            if (isset($rows[0])) {
                $moduleRow = $rows[0];
            }
            unset($rows);
        }

        $connection = new Connection();

        if (!empty($moduleRow)
            && property_exists($moduleRow, 'meta')
            && property_exists($moduleRow->meta, 'username')
            && property_exists($moduleRow->meta, 'password')
            ) {
            $connection->setBasicAuth($moduleRow->meta->username, $moduleRow->meta->password);
        }
        return $connection;
    }

    /**
     * Component Factory
     *
     * @return ComponentFactory
     */
    private function componentFactory()
    {
        return new ComponentFactory();
    }

    /**
     * Initalizes the component of the given type
     * @param string $component
     * @return \Blesta\Module\BlestaLicense\Component\AbstractComponent
     */
    private function getComponent($component)
    {
        return $this->componentFactory()
            ->create(
                $component,
                $this->Input,
                $this->getConnection(),
                $this->getModuleRow()
            );
    }
}
