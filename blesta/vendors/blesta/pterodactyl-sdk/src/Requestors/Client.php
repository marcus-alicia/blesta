<?php
namespace Blesta\PterodactylSDK\Requestors;

class Client extends \Blesta\PterodactylSDK\Requestor
{
    /**
     * Fetches a list of servers from Pterodactyl
     *
     * @return PterodactylResponse
     */
    public function getServers()
    {
        return $this->apiRequest('client');
    }

    /**
     * Fetches a server from Pterodactyl
     *
     * @param int $serverId The ID of the server to fetch
     * @return PterodactylResponse
     */
    public function getServer($serverId)
    {
        return $this->apiRequest('client/servers/' . $serverId);
    }

    /**
     * Fetches utilization stats for a server from Pterodactyl
     *
     * @param int $serverId The ID of the server for which to fetch stats
     * @return PterodactylResponse
     */
    public function getServerUtilization($serverId)
    {
        return $this->apiRequest('client/servers/' . $serverId . '/resources');
    }

    /**
     * Sends a console command to the given server from Pterodactyl
     *
     * @param int $serverId The ID of the server to which a command is being sent
     * @param string $command The command being sent
     * @return PterodactylResponse
     */
    public function serverConsoleCommand($serverId, $command)
    {
        return $this->apiRequest('client/servers/' . $serverId . '/command', ['command' => $command], 'POST');
    }

    /**
     * Sends a power signal to the given server from Pterodactyl
     *
     * @param int $serverId The ID of the server to which a power signal is being sent
     * @param string $signal The power signal to send ('start', 'stop', 'restart', or 'kill')
     * @return PterodactylResponse
     */
    public function serverPowerSignal($serverId, $signal)
    {
        return $this->apiRequest('client/servers/' . $serverId . '/power', ['signal' => $signal], 'POST');
    }
}