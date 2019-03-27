<?php

namespace Brainlabs\SA360ConversionLagCalculator;

use Exception;

class Configuration {
    /** @var string[] $config The decoded contents of the config.json */
    private $config;

    /**
     * @param string $src Location of the config file
     * @return void
     */
    public function __construct($src) 
    {
        clearstatcache();
        if (!is_readable($src)) {
            throw new Exception("no such file" .$src);
        }
        $json = file_get_contents($src);
        $config = json_decode($json, true);
        if (is_null($config)) {
            throw new Exception("invalid json" .$src);
        }
        $this->config = $config;
    }

    /**
     * @param string $key
     * @return string
     */
    private function get($key) 
    {
        if (!is_array($this->config)) {
            throw new Exception("missing key: " . $key);
        }
        return $this->config[$key];
    }

    /**
     * @return string
     */
    public function getAgencyId() 
    {
        return $this->get("agency_id");
    }

    /**
     * @return string
     */
    public function getInputSsUrl() 
    {
        return $this->get("input_ss_url");
    }

    /**
     * @return string
     */
    public function getCreds() 
    {
        return $this->get("creds");
    }
}
?>
