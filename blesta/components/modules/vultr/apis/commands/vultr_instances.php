<?php
/**
 * Vultr instance management.
 *
 * @package blesta
 * @subpackage blesta.components.modules.vultr
 * @copyright Copyright (c) 2023, Phillips Data, Inc.
 * @license http://www.blesta.com/license/ The Blesta License Agreement
 * @link http://www.blesta.com/ Blesta
 */
class VultrInstances
{
    /**
     * @var VultrApi The API to use for communication.
     */
    private $api;

    /**
     * Sets the API to use for communication.
     *
     * @param VultrApi $api The API to use for communication
     */
    public function __construct(VultrApi $api)
    {
        $this->api = $api;
    }

    /**
     * Changes the virtual machine to a different application.
     *
     * @param array $params An array containing the following arguments:
     *     - instance-id: Unique identifier for this instance.
     *     - app_id: Unique identifier of the application to use.
     * @return VultrResponse An object containing the api response.
     */
    public function appChange($params = [])
    {
        return $this->api->apiRequest(
            '/instances/' . ($params['instance-id'] ?? ''),
            ['app_id' => ($params['app_id'] ?? '')],
            'PATCH'
        );
    }

    /**
     * Retrieves a list of applications to which a virtual machine can be changed.
     *
     * @param array $params An array containing the following arguments:
     *     - instance-id: Unique identifier for this instance.
     * @return VultrResponse An object containing the api response.
     */
    public function appChangeList($params = [])
    {
        return $this->api->apiRequest(
            '/instances/' . ($params['instance-id'] ?? '') . '/upgrades',
            ['type' => 'applications']
        );
    }

    /**
     * Disables automatic backups on a server.
     *
     * @param array $params An array containing the following arguments:
     *     - instance-id: Unique identifier for this instance.
     * @return VultrResponse An object containing the api response.
     */
    public function backupDisable($params = [])
    {
        return $this->api->apiRequest(
            '/instances/' . ($params['instance-id'] ?? ''),
            ['backups' => 'disabled'],
            'PATCH'
        );
    }

    /**
     * Enables automatic backups on a server.
     *
     * @param array $params An array containing the following arguments:
     *     - instance-id: Unique identifier for this instance.
     * @return VultrResponse An object containing the api response.
     */
    public function backupEnable($params = [])
    {
        return $this->api->apiRequest(
            '/instances/' . ($params['instance-id'] ?? ''),
            ['backups' => 'enabled'],
            'PATCH'
        );
    }

    /**
     * Retrieves the backup schedule for a server.
     *
     * @param array $params An array containing the following arguments:
     *     - instance-id: Unique identifier for this instance.
     * @return VultrResponse An object containing the api response.
     */
    public function backupGetSchedule($params = [])
    {
        return $this->api->apiRequest('/instances/' . ($params['instance-id'] ?? '') . '/backup-schedule');
    }

    /**
     * Sets the backup schedule for a server.
     *
     * @param array $params An array containing the following arguments:
     *     - instance-id: Unique identifier for this instance.
     *     - type: Backup cron type. Can be one of 'daily', 'weekly', 'monthly',
     *         'daily_alt_even', or 'daily_alt_odd'.
     *     - hour: Hour value (0-23).
     *     - dow: Day-of-week value (0-6). Applicable to crons: 'weekly'.
     *     - dom: Day-of-month value (1-28). Applicable to crons: 'monthly'.
     * @return VultrResponse An object containing the api response.
     */
    public function backupSetSchedule($params = [])
    {
        $instance_id = $params['instance-id'] ?? '';
        unset($params['instance-id']);

        return $this->api->apiRequest('/instances/' . $instance_id . '/backup-schedule', $params, 'POST');
    }

    /**
     * Get the bandwidth used by a virtual machine.
     *
     * @param array $params An array containing the following arguments:
     *     - instance-id: Unique identifier for this instance.
     * @return VultrResponse An object containing the api response.
     */
    public function bandwidth($params = [])
    {
        return $this->api->apiRequest('/instances/' . ($params['instance-id'] ?? '') . '/bandwidth');
    }

    /**
     * Create a new virtual machine. You will start being billed for this immediately.
     * The response only contains the SUBID for the new machine.
     *
     * In order to create a server using a snapshot, use OSID 164 and specify a SNAPSHOTID.
     * Similarly, to create a server using an ISO use OSID 159 and specify an ISOID.
     *
     * @param array $params An array containing the following arguments:
     *     - region: Location to create this virtual machine in.
     *     - plan: Plan to use when creating this virtual machine.
     *     - os_id: Operating system to use.
     *     - ipxe_chain_url: If you've selected the 'custom' operating system,
     *         this can be set to chainload the specified URL on bootup, via iPXE. (optional)
     *     - iso_id: If you've selected the 'custom' operating system, this is the ID of a
     *         specific ISO to mount during the deployment. (optional)
     *     - script_id: If you've not selected a 'custom' operating system, this can be the
     *         script_id of a startup script to execute on boot. (optional)
     *     - snapshot_id: If you've selected the 'snapshot' operating system, this
     *         should be the SNAPSHOTID. (optional)
     *     - enable_ipv6: If true, an IPv6 subnet will be assigned. (optional)
     *     - attach_vpc: An array of VPC IDs to attach to this Instance. This parameter
     *          takes precedence over enable_vpc. Please choose one parameter. (optional)
     *     - label: This is a text label that will be shown in the control panel. (optional)
     *     - sshkey_id: List of SSH keys to apply to this server on install. (optional)
     *     - backups: 'enabled' or 'disabled'.  If enabled, automatic backups will be enabled. (optional)
     *     - app_id: If launching an application (OSID 186), this is the APPID to launch. (optional)
     *     - user_data: Base64 encoded user-data. (optional)
     *     - activation_email: 'true' or 'false'. If true, an activation email will be sent. (optional)
     *     - ddos_protection: 'true' or 'false'.  If true, DDOS protection will be enabled. (optional)
     *     - reserved_ipv4: IP address of the floating IP to use on this server. (optional)
     *     - hostname: The hostname to assign to this server. (optional)
     *     - tags: The tags to assign to this server. (optional)
     *     - firewall_group_id: The firewall group to assign to this server. (optional)
     * @return VultrResponse An object containing the api response.
     */
    public function create($params = [])
    {
        return $this->api->apiRequest('/instances', $params, 'POST');
    }

    /**
     * Add a new IPv4 address to a server. You will start being billed for this immediately.
     * The server will be rebooted unless you specify otherwise. You must reboot the server
     * before the IPv4 address can be configured.
     *
     * @param array $params An array containing the following arguments:
     *     - instance-id: Unique identifier for this instance.
     *     - reboot: 'yes' or 'no'. If yes, the server is rebooted immediately. (optional)
     * @return VultrResponse An object containing the api response.
     */
    public function createIpv4($params = [])
    {
        return $this->api->apiRequest(
            '/instances/' . ($params['instance-id'] ?? '') . '/ipv4',
            ['reboot' => $params['reboot'] ?? ''],
            'POST'
        );
    }

    /**
     * Destroy (delete) a virtual machine. All data will be permanently lost, and the IP
     * address will be released.
     *
     * @param array $params An array containing the following arguments:
     *     - instance-id: Unique identifier for this instance.
     * @return VultrResponse An object containing the api response.
     */
    public function destroy($params = [])
    {
        return $this->api->apiRequest('/instances/' . ($params['instance-id'] ?? ''), [], 'DELETE');
    }

    /**
     * Fetches a virtual machine.
     *
     * @param array $params An array containing the following arguments:
     *     - instance-id: Unique identifier for this instance.
     * @return VultrResponse An object containing the api response.
     */
    public function get($params = [])
    {
        return $this->api->apiRequest('/instances/' . ($params['instance-id'] ?? ''));
    }

    /**
     * Removes a secondary IPv4 address from a server. Your server will be hard-restarted.
     * We suggest halting the machine gracefully before removing IPs.
     *
     * @param array $params An array containing the following arguments:
     *     - instance-id: Unique identifier for this instance.
     *     - ipv4: IPv4 address to remove.
     * @return VultrResponse An object containing the api response.
     */
    public function destroyIpv4($params = [])
    {
        return $this->api->apiRequest(
            '/instances/' . ($params['instance-id'] ?? '') . '/ipv4/' . ($params['ipv4'] ?? ''),
            [],
            'DELETE'
        );
    }

    /**
     * Set, change, or remove the firewall group currently applied to a server.
     *
     * @param array $params An array containing the following arguments:
     *     - instance-id: Unique identifier for this instance.
     *     - firewall_group_id: The firewall group to apply to this server.
     *         A value of "0" means "no firewall group".
     * @return VultrResponse An object containing the api response.
     */
    public function firewallGroupSet($params = [])
    {
        return $this->api->apiRequest(
            '/instances/' . ($params['instance-id'] ?? ''),
            ['firewall_group_id' => ($params['firewall_group_id'] ?? '')],
            'PATCH'
        );
    }

    /**
     * Retrieves the (base64 encoded) user-data for this subscription.
     *
     * @param array $params An array containing the following arguments:
     *     - instance-id: Unique identifier for this instance.
     * @return VultrResponse An object containing the api response.
     */
    public function getUserData($params = [])
    {
        return $this->api->apiRequest('/instances/' . ($params['instance-id'] ?? '') . '/user-data/');
    }

    /**
     * Halt a virtual machine.
     *
     * @param array $params An array containing the following arguments:
     *     - instance_id: Unique identifier for this instance.
     * @return VultrResponse An object containing the api response.
     */
    public function halt($params = [])
    {
        return $this->api->apiRequest('/instances/' . ($params['instance-id'] ?? '') . '/halt', $params, 'POST');
    }

    /**
     * Enables IPv6 networking on a server by assigning an IPv6 subnet to it.
     *
     * @param array $params An array containing the following arguments:
     *     - instance-id: Unique identifier for this instance.
     * @return VultrResponse An object containing the api response.
     */
    public function ipv6Enable($params = [])
    {
        return $this->api->apiRequest(
            '/instances/' . ($params['instance-id'] ?? ''),
            ['enable_ipv6' => true],
            'PATCH'
        );
    }

    /**
     * Attach an ISO and reboot the server.
     *
     * @param array $params An array containing the following arguments:
     *     - instance-id: Unique identifier for this instance.
     *     - iso_id: The ISO that will be mounted.
     * @return VultrResponse An object containing the api response.
     */
    public function isoAttach($params = [])
    {
        return $this->api->apiRequest(
            '/instances/' . ($params['instance-id'] ?? '') . '/iso/attach',
            ['iso_id' => $params['iso_id'] ?? ''],
            'POST'
        );
    }

    /**
     * Detach the currently mounted ISO and reboot the server.
     *
     * @param array $params An array containing the following arguments:
     *     - instance-id: Unique identifier for this instance.
     * @return VultrResponse An object containing the api response.
     */
    public function isoDetach($params = [])
    {
        return $this->api->apiRequest('/instances/' . ($params['instance-id'] ?? '') . '/iso/detach', [], 'POST');
    }

    /**
     * Retrieve the current ISO state for a given subscription. The returned
     * state may be one of: ready | isomounting | isomounted.
     *
     * @param array $params An array containing the following arguments:
     *     - instance-id: Unique identifier for this instance.
     * @return VultrResponse An object containing the api response.
     */
    public function isoStatus($params = [])
    {
        return $this->api->apiRequest('/instances/' . ($params['instance-id'] ?? '') . '/iso', $params);
    }

    /**
     * Set the label of a virtual machine.
     *
     * @param array $params An array containing the following arguments:
     *     - instance-id: Unique identifier for this instance.
     *     - label: This is a text label that will be shown in the control panel.
     * @return VultrResponse An object containing the api response.
     */
    public function labelSet($params = [])
    {
        return $this->api->apiRequest(
            '/instances/' . ($params['instance-id'] ?? ''),
            ['label' => ($params['label'] ?? '')],
            'PATCH'
        );
    }

    /**
     * List all active or pending virtual machines on the current account.
     *
     * @param array $params An array containing the following arguments:
     *     - per_page: Number of items requested per page. Default is 100 and Max is 500. (optional)
     *     - cursor: Cursor for paging. See Meta and Pagination. (optional)
     *     - label: Filter by label. (optional)
     *     - main_ip: Filter by main ip address. (optional)
     *     - region: Filter by Region id. (optional)
     * @return VultrResponse An object containing the api response.
     */
    public function listServers($params = [])
    {
        return $this->api->apiRequest('/instances', $params);
    }

    /**
     * List the IPv4 information of a virtual machine. IP information is only available
     * for virtual machines in the "active" state.
     *
     * @param array $params An array containing the following arguments:
     *     - instance-id: Unique identifier for this instance.
     *     - public_network: If true, includes information about the public
     *          network adapter (such as MAC address) with the main_ip entry.
     *     - per_page: Number of items requested per page. Default is 100 and Max is 500. (optional)
     *     - cursor: Cursor for paging. See Meta and Pagination. (optional)
     * @return VultrResponse An object containing the api response.
     */
    public function listIpv4($params = [])
    {
        $instance_id = $params['instance-id'] ?? '';
        unset($params['instance-id']);

        return $this->api->apiRequest(
            '/instances/' . $instance_id . '/ipv4',
            $params
        );
    }

    /**
     * List the IPv6 information of a virtual machine. IP information is only available
     * for virtual machines in the "active" state.
     *
     * @param array $params An array containing the following arguments:
     *     - instance-id: Unique identifier for this instance.
     * @return VultrResponse An object containing the api response.
     */
    public function listIpv6($params = [])
    {
        return $this->api->apiRequest(
            '/instances/' . ($params['instance-id'] ?? '') . '/ipv6'
        );
    }

    /**
     * Determine what other subscriptions are hosted on the same physical host as a
     * given subscription.
     *
     * @param array $params An array containing the following arguments:
     *     - instance-id: Unique identifier for this instance.
     * @return VultrResponse An object containing the api response.
     */
    public function neighbors($params = [])
    {
        return $this->api->apiRequest('/instances/' . ($params['instance-id'] ?? '') . '/neighbors');
    }

    /**
     * Changes the virtual machine to a different operating system. All data will
     * be permanently lost.
     *
     * @param array $params An array containing the following arguments:
     *     - instance-id: Unique identifier for this instance.
     *     - os_id: Operating system to use.
     * @return VultrResponse An object containing the api response.
     */
    public function osChange($params = [])
    {
        return $this->api->apiRequest(
            '/instances/' . ($params['instance-id'] ?? ''),
            ['os_id' => ($params['os_id'] ?? '')],
            'PATCH'
        );
    }

    /**
     * Retrieves a list of operating systems to which a virtual machine can be changed.
     * Always check against this list before trying to switch operating systems because
     * it is not possible to switch between every operating system combination.
     *
     * @param array $params An array containing the following arguments:
     *     - instance-id: Unique identifier for this instance.
     * @return VultrResponse An object containing the api response.
     */
    public function osChangeList($params = [])
    {
        return $this->api->apiRequest(
            '/instances/' . ($params['instance-id'] ?? '') . '/upgrades',
            ['type' => 'os']
        );
    }

    /**
     * Reboot a virtual machine.
     *
     * @param array $params An array containing the following arguments:
     *     - instance-id: The Instance IDs to reboot.
     * @return VultrResponse An object containing the api response.
     */
    public function reboot($params = [])
    {
        return $this->api->apiRequest('/instances/' . ($params['instance-id'] ?? '') . '/reboot', $params, 'POST');
    }

    /**
     * Reinstall the operating system on a virtual machine. All data will be permanently
     * lost, but the IP address will remain the same. There is no going back from this call.
     *
     * @param array $params An array containing the following arguments:
     *     - instance-id: Unique identifier for this instance.
     *     - hostname: The new hostname to assign to this server. (optional)
     * @return VultrResponse An object containing the api response.
     */
    public function reinstall($params = [])
    {
        return $this->api->apiRequest(
            '/instances/' . ($params['instance-id'] ?? '') . '/reinstall',
            ['hostname' => $params['hostname'] ?? ''],
            'POST'
        );
    }

    /**
     * Restore the specified backup to the virtual machine. Any data already on the virtual
     * machine will be lost.
     *
     * @param array $params An array containing the following arguments:
     *     - instance-id: Unique identifier for this instance.
     *     - backup_id: The id of the backup to restore.
     * @return VultrResponse An object containing the api response.
     */
    public function restoreBackup($params = [])
    {
        return $this->api->apiRequest(
            '/instances/' . ($params['instance-id'] ?? '') . '/restore',
            ['backup_id' => $params['backup_id'] ?? ''],
            'POST'
        );
    }

    /**
     * Restore the specified snapshot to the virtual machine. Any data already on the virtual
     * machine will be lost.
     *
     * @param array $params An array containing the following arguments:
     *     - instance-id: Unique identifier for this instance.
     *     - snapshot_id: The id of the snapshot to restore.
     * @return VultrResponse An object containing the api response.
     */
    public function restoreSnapshot($params = [])
    {
        return $this->api->apiRequest(
            '/instances/' . ($params['instance-id'] ?? '') . '/restore',
            ['snapshot_id' => $params['snapshot_id'] ?? ''],
            'POST'
        );
    }

    /**
     * Set a reverse DNS entry for an IPv4 address of a virtual machine to the
     * original setting.
     *
     * @param array $params An array containing the following arguments:
     *     - instance-id: Unique identifier for this instance.
     *     - ip: IPv4 address used in the reverse DNS update.
     *     - reverse: The IPv4 reverse entry.
     * @return VultrResponse An object containing the api response.
     */
    public function reverseDefaultIpv4($params = [])
    {
        return $this->api->apiRequest(
            '/instances/' . ($params['instance-id'] ?? '') . '/ipv4/reverse',
            [
                'ip' => $params['ip'] ?? '',
                'reverse' => $params['reverse'] ?? ''
            ],
            'POST'
        );
    }

    /**
     * Set a reverse DNS entry for an IPv6 address of a virtual machine to the
     * original setting.
     *
     * @param array $params An array containing the following arguments:
     *     - instance-id: Unique identifier for this instance.
     *     - ip: IPv6 address used in the reverse DNS update.
     *     - reverse: The IPv6 reverse entry.
     * @return VultrResponse An object containing the api response.
     */
    public function reverseDefaultIpv6($params = [])
    {
        return $this->api->apiRequest(
            '/instances/' . ($params['instance-id'] ?? '') . '/ipv6/reverse',
            [
                'ip' => $params['ip'] ?? '',
                'reverse' => $params['reverse'] ?? ''
            ],
            'POST'
        );
    }

    /**
     * List the IPv6 reverse DNS entries of a virtual machine. Reverse DNS entries
     * are only available for virtual machines in the "active" state. If the virtual
     * machine does not have IPv6 enabled, then an empty array is returned.
     *
     * @param array $params An array containing the following arguments:
     *     - instance-id: Unique identifier for this instance.
     * @return VultrResponse An object containing the api response.
     */
    public function reverseListIpv6($params = [])
    {
        return $this->api->apiRequest('/instances/' . ($params['instance-id'] ?? '') . '/ipv6/reverse');
    }

    /**
     * Set a reverse DNS entry for an IPv4 address of a virtual machine.
     *
     * @param array $params An array containing the following arguments:
     *     - instance-id: Unique identifier for this instance.
     *     - ip: IPv4 address used in the reverse DNS update.
     * @return VultrResponse An object containing the api response.
     */
    public function reverseSetIpv4($params = [])
    {
        return $this->reverseDefaultIpv4($params);
    }

    /**
     * Set a reverse DNS entry for an IPv6 address of a virtual machine.
     *
     * @param array $params An array containing the following arguments:
     *     - instance-id: Unique identifier for this instance.
     *     - ip: IPv6 address used in the reverse DNS update.
     * @return VultrResponse An object containing the api response.
     */
    public function reverseSetIpv6($params = [])
    {
        return $this->reverseDefaultIpv6($params);
    }

    /**
     * Sets the user-data for this subscription. User-data is a generic data store,
     * which some provisioning tools and cloud operating systems use as a configuration
     * file. It is generally consumed only once after an instance has been launched,
     * but individual needs may vary.
     *
     * @param array $params An array containing the following arguments:
     *     - instance-id: Unique identifier for this instance.
     *     - user_data: Base64 encoded user-data.
     * @return VultrResponse An object containing the api response.
     */
    public function setUserData($params = [])
    {
        return $this->api->apiRequest(
            '/instances/' . ($params['instance-id'] ?? ''),
            ['user_data' => ($params['user_data'] ?? '')],
            'PATCH'
        );
    }

    /**
     * Start a virtual machine. If the machine is already running, it will be restarted.
     *
     * @param array $params An array containing the following arguments:
     *     - instance-id: Unique identifier for this instance.
     * @return VultrResponse An object containing the api response.
     */
    public function start($params = [])
    {
        return $this->api->apiRequest('/instances/' . ($params['instance-id'] ?? '') . '/start', $params, 'POST');
    }

    /**
     * Set the tag of a virtual machine.
     *
     * @param array $params An array containing the following arguments:
     *     - instance-id: Unique identifier for this instance.
     *     - tags: The tag to assign to this server. This tag is shown in the control panel.
     * @return VultrResponse An object containing the api response.
     */
    public function tagSet($params = [])
    {
        return $this->api->apiRequest(
            '/instances/' . ($params['instance-id'] ?? ''),
            ['tags' => ($params['tags'] ?? '')],
            'PATCH'
        );
    }

    /**
     * Upgrade the plan of a virtual machine. The virtual machine will be rebooted upon a
     * successful upgrade.
     *
     * @param array $params An array containing the following arguments:
     *     - instance-id: Unique identifier for this instance.
     *     - plan: The id of the new plan.
     * @return VultrResponse An object containing the api response.
     */
    public function upgradePlan($params = [])
    {
        return $this->api->apiRequest(
            '/instances/' . ($params['instance-id'] ?? ''),
            ['plan' => ($params['plan'] ?? '')],
            'PATCH'
        );
    }

    /**
     * Upgrade the plan of a virtual machine. The virtual machine will be rebooted upon a
     * successful upgrade.
     *
     * @param array $params An array containing the following arguments:
     *     - instance-id: Unique identifier for this instance.
     * @return VultrResponse An object containing the api response.
     */
    public function upgradePlanList($params = [])
    {
        return $this->api->apiRequest(
            '/instances/' . ($params['instance-id'] ?? '') . '/upgrades',
            ['type' => 'plans']
        );
    }
}
