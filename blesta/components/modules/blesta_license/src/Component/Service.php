<?php
namespace Blesta\Module\BlestaLicense\Component;

class Service extends AbstractComponent
{
    /**
     * Add a new license
     *
     * @param stdClass $package
     * @param array $vars
     * @param stdClass $parent_package
     * @param stdClass $parent_service
     * @param string $status
     * @return \Blesta\ResellerApi\ResponseInterface
     */
    public function add(
        $package,
        array $vars = null,
        $parent_package = null,
        $parent_service = null,
        $status = 'pending'
    ) {
        $data = array(
            'pricing_id' => $package->meta->pricing_id
        );
        if ($this->testModeEnabled()) {
            $data['test_mode'] = 'true';
        }

        return $this->licenses()->add($data);
    }

    /**
     * Update a license
     *
     * @param stdClass $package
     * @param stdClass $service
     * @param array $vars
     * @param stdClass $parent_package
     * @param stdClass $parent_service
     * @return \Blesta\ResellerApi\ResponseInterface
     */
    public function edit(
        $package,
        $service,
        array $vars = array(),
        $parent_package = null,
        $parent_service = null
    ) {
        $fields = $this->serviceFieldsToArray($service->fields);
        $data = array(
            'license' => $fields['license'],
            'reissue_status' => (isset($vars['reissue']) && $vars['reissue'] === 'true')
                ? 'reissue'
                : ''
        );
        if ($this->testModeEnabled()) {
            $data['test_mode'] = 'true';
        }

        return $this->licenses()->update($data);
    }

    /**
     * Suspend a license
     *
     * @param stdClass $package
     * @param stdClass $service
     * @param stdClass $parent_package
     * @param stdClass $parent_service
     * @return \Blesta\ResellerApi\ResponseInterface
     */
    public function suspend(
        $package,
        $service,
        $parent_package = null,
        $parent_service = null
    ) {
        $fields = $this->serviceFieldsToArray($service->fields);
        $data = array(
            'license' => $fields['license']
        );
        if ($this->testModeEnabled()) {
            $data['test_mode'] = 'true';
        }

        return $this->licenses()->suspend($data);
    }

    /**
     * Unsuspend a license
     *
     * @param stdClass $package
     * @param stdClass $service
     * @param stdClass $parent_package
     * @param stdClass $parent_service
     * @return \Blesta\ResellerApi\ResponseInterface
     */
    public function unsuspend(
        $package,
        $service,
        $parent_package = null,
        $parent_service = null
    ) {
        $fields = $this->serviceFieldsToArray($service->fields);
        $data = array(
            'license' => $fields['license']
        );
        if ($this->testModeEnabled()) {
            $data['test_mode'] = 'true';
        }

        return $this->licenses()->unsuspend($data);
    }

    /**
     * Cancel a license
     *
     * @param stdClass $package
     * @param stdClass $service
     * @param stdClass $parent_package
     * @param stdClass $parent_service
     * @return \Blesta\ResellerApi\ResponseInterface
     */
    public function cancel(
        $package,
        $service,
        $parent_package = null,
        $parent_service = null
    ) {
        $fields = $this->serviceFieldsToArray($service->fields);
        $data = array(
            'license' => $fields['license']
        );
        if ($this->testModeEnabled()) {
            $data['test_mode'] = 'true';
        }

        return $this->licenses()->cancel($data);
    }

    /**
     * Initialize the license API
     *
     * @return \Blesta\ResellerApi\Command\Licenses
     */
    protected function licenses()
    {
        return $this->factory->create('Licenses', $this->connection);
    }

    /**
     * Convert service fields to key/value pairs
     *
     * @param stdClass $fields
     * @return array
     */
    protected function serviceFieldsToArray($fields)
    {
        $data = array();
        foreach ($fields as $field) {
            $data[$field->key] = $field->value;
        }
        return $data;
    }
}
