<?php
namespace Blesta\Core\Util\Validate;

class Server
{
    /**
     * Determines whether the given domain is a valid domain name.
     * NOTE: This does not check IDN/punycode/UTF-8 domains
     *
     * @param string $domain The domain or subdomain to check (e.g. "domain.com", "sub.domain.com")
     * @return bool True if the $domain is valid, false otherwise
     */
    public function isDomain($domain)
    {
        // Domain may not exceed 255 characters in length
        if (strlen($domain) > 255) {
            return false;
        }

        return (bool)preg_match('/^((?!-)[a-z0-9-]{1,63}(?<!-)\.)+[a-z]{2,}$/i', $domain);
    }

    /**
     * Determines whether the given IP is a valid IP address
     *
     * @param string $ip The IP address to validate
     * @return bool True if the IP address is valid, false otherwise
     */
    public function isIp($ip)
    {
        return (bool)filter_var($ip, FILTER_VALIDATE_IP);
    }

    /**
     * Determines whether the given URL is valid
     *required
     * @param string $url The URL to validate (protocol not required, e.g. "http://")
     * @return bool True if the URL is valid, false otherwise
     */
    public function isUrl($url)
    {
        // Add a protocol to the beginning of the URL to pass validation
        $parts = parse_url($url);
        if ($parts && !isset($parts['scheme']) && substr($url, 0, 2) !== '//') {
            $url = 'http://' . $url;
        }

        return (bool)filter_var($url, FILTER_VALIDATE_URL);
    }
}
