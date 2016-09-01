<?php

namespace Jcroll\FoursquareApiClient\Client;

use GuzzleHttp\Command\Guzzle\Description;
use GuzzleHttp\Command\Guzzle\GuzzleClient;
use GuzzleHttp\Client;

class FoursquareClient extends GuzzleClient
{
    /**
     * {@inheritdoc}
     */
    public static function factory($config = [])
    {
        $required = ['client_id', 'client_secret'];

        foreach ($required as $value) {
            if (!isset($config[$value]) || !$config[$value]) {
                throw new \InvalidArgumentException(sprintf('Argument "%s" is required.', $value));
            }
        }

        $version = isset($config['version']) ? (int) $config['version'] : 20160901;
        $mode    = isset($config['mode']) ? $config['mode'] : 'foursquare';

        static::validateVersion($version);
        static::validateMode($mode);

        $client = new Client([
            'base_url' => 'https://api.foursquare.com/v2/',
            'defaults' => [
                'query' => [
                    'client_id'     => $config['client_id'],
                    'client_secret' => $config['client_secret'],
                    'v'             => $version,
                    'm'             => $mode,
                ],
            ]
        ]);

        $directory   = $version >= 20160901 ? 20160901 : 20130707;
        $contents    = file_get_contents(sprintf('%s/../Resources/config/%s/client.json', __DIR__, $directory));
        $description = new Description(json_decode($contents, true));

        return new static($client, $description);
    }

    /**
     * @param string $token
     *
     * @return $this
     */
    public function addToken($token)
    {
        $this->getHttpClient()->setDefaultOption('query/oauth_token', $token);

        return $this;
    }

    /**
     * @param string $mode
     *
     * @return $this
     *
     * @throws \InvalidArgumentException
     */
    public function setMode($mode)
    {
        static::validateMode($mode);

        $this->getHttpClient()->setDefaultOption('query/m', $mode);

        return $this;
    }

    /**
     * @param int $version
     *
     * @throws \InvalidArgumentException
     */
    private static function validateVersion($version)
    {
        if (8 === strlen($version)) {
            $month = (int) substr($version, 4, 2);
            $day   = (int) substr($version, 6, 2);
            $year  = (int) substr($version, 0, 4);

            if (checkdate($month, $day, $year)) {
                return;
            }
        }

        throw new \InvalidArgumentException(sprintf('"%d" is an invalid version.', $version));
    }

    /**
     * @param string $mode
     *
     * @throws \InvalidArgumentException
     */
    private static function validateMode($mode)
    {
        $modes = ['foursquare', 'swarm'];

        if (!in_array($mode, $modes)) {
            throw new \InvalidArgumentException('Acceptable values for "mode" are "foursquare" or "swarm".');
        }
    }
}