<?php
/**
 * SolusVM Virtual Server Management
 *
 * @copyright Copyright (c) 2013, Phillips Data, Inc.
 * @license http://opensource.org/licenses/mit-license.php MIT License
 * @package solusvm.commands
 */
class SolusvmVserver
{
    /**
     * @var SolusvmApi
     */
    private $api;

    /**
     * Sets the API to use for communication
     *
     * @param SolusvmApi $api The API to use for communication
     */
    public function __construct(SolusvmApi $api)
    {
        $this->api = $api;
    }

    /**
     * Create a Virtual Server
     *
     * @param array $vars An array of input params including:
     *  - node Name of the node
     *  - nodegroup Name of the node group
     *  - hostname Hostname of the virtual server
     *  - password Root password
     *  - username Client username
     *  - plan Plan name
     *  - template Template or ISO name
     *  - ips The number of IP addresses
     *  - hvmt Allow templates and ISOs for Xen HVM (0, 1 default 0)
     *  - custommemory Override plan memory with this amount in bytes
     *  - customdiskspace Override plan diskspace with this amount in bytes
     *  - custombandwidth Override plan diskspace with this amount in bits
     *  - customcpu Override plan CPU cores with this amount
     *  - customextraip The number of extra IP addresses
     *  - issuelicense (1 = cPanel monthly, 2 = cPanel yearly)
     *  - internalip (0, 1 default 0)
     * @return SolusvmResponse
     */
    public function create(array $vars)
    {
        return $this->api->submit('vserver-create', $vars);
    }

    /**
     * Check if a Virtual Server exists
     *
     * @param array $vars An array of input params including:
     *  - vserverid The virtual server ID
     * @return SolusvmResponse
     */
    public function checkExists(array $vars)
    {
        return $this->api->submit('vserver-checkexists', $vars);
    }

    /**
     * Check if a Virtual Server status
     *
     * @param array $vars An array of input params including:
     *  - vserverid The virtual server ID
     * @return SolusvmResponse
     */
    public function status(array $vars)
    {
        return $this->api->submit('vserver-status', $vars);
    }

    /**
     * Add IP address
     *
     * @param array $vars An array of input params including:
     *  - vserverid The virtual server ID
     * @return SolusvmResponse
     */
    public function addIp(array $vars)
    {
        return $this->api->submit('vserver-addip', $vars);
    }

    /**
     * Delete an IP address
     *
     * @param array $vars An array of input params including:
     *  - vserverid The virtual server ID
     *  - ipaddr The IP address
     * @return SolusvmResponse
     */
    public function deleteIp(array $vars)
    {
        return $this->api->submit('vserver-delip', $vars);
    }

    /**
     * Updates the bandwidth limit
     *
     * @param array $vars An array of input params including:
     *  - limit The bandwidth limit in GB
     *  - overlimit The bandwith over limit in GB
     * @return SolusvmResponse
     */
    public function changeBandwidth(array $vars)
    {
        return $this->api->submit('vserver-bandwidth', $vars);
    }

    /**
     * Updates the memory and burst/swap limit
     *
     * @param array $vars An array of input params including:
     *  - memory The amount of memory to change to in MB
     * @return SolusvmResponse
     */
    public function changeMemory(array $vars)
    {
        return $this->api->submit('vserver-change-memory', $vars);
    }

    /**
     * Updates the hard disk space limit
     *
     * @param array $vars An array of input params including:
     *  - hdd The hard disk space in GB
     * @return SolusvmResponse
     */
    public function changeHdd(array $vars)
    {
        return $this->api->submit('vserver-change-hdd', $vars);
    }

    /**
     * Updates the number of CPUs
     *
     * @param array $vars An array of input params including:
     *  - cpu The number of CPUs
     * @return SolusvmResponse
     */
    public function changeCpu(array $vars)
    {
        return $this->api->submit('vserver-change-cpu', $vars);
    }

    /**
     * Change owner of a Virtual Server
     *
     * @param array $vars An array of input params including:
     *  - vserverid The virtual server ID
     *  - clientid The new client ID
     * @return SolusvmResponse
     */
    public function changeOwner(array $vars)
    {
        return $this->api->submit('vserver-changeowner', $vars);
    }

    /**
     * Reboot a Virtual Server
     *
     * @param array $vars An array of input params including:
     *  - vserverid The virtual server ID
     * @return SolusvmResponse
     */
    public function reboot(array $vars)
    {
        return $this->api->submit('vserver-reboot', $vars);
    }

    /**
     * Shutdown a Virtual Server
     *
     * @param array $vars An array of input params including:
     *  - vserverid The virtual server ID
     * @return SolusvmResponse
     */
    public function shutdown(array $vars)
    {
        return $this->api->submit('vserver-shutdown', $vars);
    }

    /**
     * Boot a Virtual Server
     *
     * @param array $vars An array of input params including:
     *  - vserverid The virtual server ID
     * @return SolusvmResponse
     */
    public function boot(array $vars)
    {
        return $this->api->submit('vserver-boot', $vars);
    }

    /**
     * Suspend a Virtual Server
     *
     * @param array $vars An array of input params including:
     *  - vserverid The virtual server ID
     * @return SolusvmResponse
     */
    public function suspend(array $vars)
    {
        return $this->api->submit('vserver-suspend', $vars);
    }

    /**
     * Unsuspend a Virtual Server
     *
     * @param array $vars An array of input params including:
     *  - vserverid The virtual server ID
     * @return SolusvmResponse
     */
    public function unsuspend(array $vars)
    {
        return $this->api->submit('vserver-unsuspend', $vars);
    }

    /**
     * Enable TUN/TAP
     *
     * @param array $vars An array of input params including:
     *  - vserverid The virtual server ID
     * @return SolusvmResponse
     */
    public function tunEnable(array $vars)
    {
        return $this->api->submit('vserver-tun-enable', $vars);
    }

    /**
     * Disable TUN/TAP
     *
     * @param array $vars An array of input params including:
     *  - vserverid The virtual server ID
     * @return SolusvmResponse
     */
    public function tunDisable(array $vars)
    {
        return $this->api->submit('vserver-tun-disable', $vars);
    }

    /**
     * Enable Network mode
     *
     * @param array $vars An array of input params including:
     *  - vserverid The virtual server ID
     * @return SolusvmResponse
     */
    public function networkEnable(array $vars)
    {
        return $this->api->submit('vserver-network-enable', $vars);
    }

    /**
     * Disable Network mode
     *
     * @param array $vars An array of input params including:
     *  - vserverid The virtual server ID
     * @return SolusvmResponse
     */
    public function networkDisable(array $vars)
    {
        return $this->api->submit('vserver-network-disable', $vars);
    }

    /**
     * Change serial console password
     *
     * @param array $vars An array of input params including:
     *  - vserverid The virtual server ID
     *  - consolepassword New password
     * @return SolusvmResponse
     */
    public function consolePass(array $vars)
    {
        return $this->api->submit('vserver-consolepass', $vars);
    }

    /**
     * Change VNC password
     *
     * @param array $vars An array of input params including:
     *  - vserverid The virtual server ID
     *  - vncpassword New password
     * @return SolusvmResponse
     */
    public function vncPass(array $vars)
    {
        return $this->api->submit('vserver-vncpass', $vars);
    }

    /**
     * Get VNC info
     *
     * @param array $vars An array of input params including:
     *  - vserverid The virtual server ID
     * @return SolusvmResponse
     */
    public function vnc(array $vars)
    {
        return $this->api->submit('vserver-vnc', $vars);
    }

    /**
     * Get serial console info
     *
     * @param array $vars An array of input params including:
     *  - vserverid The virtual server ID
     *  - access If enabling or disabling a session (enable, disable optional)
     *  - time Specified with the access parameter (1, 2, 3, 4, 5, 6, 7, 8 optional)
     * @return SolusvmResponse
     */
    public function console(array $vars)
    {
        return $this->api->submit('vserver-console', $vars);
    }

    /**
     * Mount an ISO
     *
     * @param array $vars An array of input params including:
     *  - vserverid The virtual server ID
     *  - iso The filname of the ISO
     * @return SolusvmResponse
     */
    public function mountIso(array $vars)
    {
        return $this->api->submit('vserver-mountiso', $vars);
    }

    /**
     * Unmount an ISO
     *
     * @param array $vars An array of input params including:
     *  - vserverid The virtual server ID
     * @return SolusvmResponse
     */
    public function unmountIso(array $vars)
    {
        return $this->api->submit('vserver-unmountiso', $vars);
    }

    /**
     * Enable or Disable PAE
     *
     * @param array $vars An array of input params including:
     *  - vserverid The virtual server ID
     *  - pae (on, off)
     * @return SolusvmResponse
     */
    public function pae(array $vars)
    {
        return $this->api->submit('vserver-pae', $vars);
    }

    /**
     * Change the boot order
     *
     * @param array $vars An array of input params including:
     *  - vserverid The virtual server ID
     *  - bootorder (cd = Hard Disk/CDROM, dc = CDROM/Hard Disk, c = Hard Disk, d = CDROM)
     * @return SolusvmResponse
     */
    public function bootorder(array $vars)
    {
        return $this->api->submit('vserver-bootorder', $vars);
    }

    /**
     * Terminate a Virtual Server
     *
     * @param array $vars An array of input params including:
     *  - vserverid The virtual server ID
     *  - deleteclient (true, false)
     * @return SolusvmResponse
     */
    public function terminate(array $vars)
    {
        return $this->api->submit('vserver-terminate', $vars);
    }

    /**
     * Rebuild a Virtual Server
     *
     * @param array $vars An array of input params including:
     *  - vserverid The virtual server ID
     *  - template Template filename without extension
     * @return SolusvmResponse
     */
    public function rebuild(array $vars)
    {
        return $this->api->submit('vserver-rebuild', $vars);
    }

    /**
     * Change Virtual Server plan
     *
     * @param array $vars An array of input params including:
     *  - vserverid The virtual server ID
     *  - plan New plan name
     * @return SolusvmResponse
     */
    public function change(array $vars)
    {
        return $this->api->submit('vserver-change', $vars);
    }

    /**
     * Change Virtual Server root password
     *
     * @param array $vars An array of input params including:
     *  - vserverid The virtual server ID
     *  - rootpassword New root password
     * @return SolusvmResponse
     */
    public function rootPassword(array $vars)
    {
        return $this->api->submit('vserver-rootpassword', $vars);
    }

    /**
     * Change Virtual Server hostname
     *
     * @param array $vars An array of input params including:
     *  - vserverid The virtual server ID
     *  - hostname New hostname
     * @return SolusvmResponse
     */
    public function hostname(array $vars)
    {
        return $this->api->submit('vserver-hostname', $vars);
    }

    /**
     * Get Virtual Server information
     *
     * @param array $vars An array of input params including:
     *  - vserverid The virtual server ID
     *  - reboot (true, false only required for Xen PV, default false)
     * @return SolusvmResponse
     */
    public function info(array $vars)
    {
        return $this->api->submit('vserver-info', $vars);
    }

    /**
     * Get Virtual Server state
     *
     * @param array $vars An array of input params including:
     *  - vserverid The virtual server ID
     *  - nostatus Do not get the virtual server status (true, false)
     *  - nographs Do not generate graphs (true, false)
     * @return SolusvmResponse
     */
    public function infoAll(array $vars)
    {
        return $this->api->submit('vserver-infoall', $vars);
    }
}
