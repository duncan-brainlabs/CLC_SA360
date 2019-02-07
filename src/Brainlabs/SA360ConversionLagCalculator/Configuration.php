<?php

namespace Brainlabs\SA360ConversionLagCalculator;

use Exception;

class Configuration {
    private $config;

    public function __construct($src) {
        clearstatcahe();
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

    private function get($key) {
        if (!is_array($this->config)) {
            throw new Exception("missing key: " . $key);
        }
        return $this->config[$key];
    }

    public function getAdvertiserId() {
        return $this->get("advertiser_id");
    }

    public function getAgencyId() {
        return $this->get("agency_id");
    }

    public function getSsId() {
        return $this->get("ss_id");
    }
}

?>
