<?php

namespace moloni;

use moloni;

class debug
{
    /**
     * @var moloni
     */
    protected $moloni;

    public $active = true;
    private $debug_logs = array();

    public function __construct(moloni $moloni)
    {
        $this->moloni = $moloni;
    }

    public function __set($name, $value)
    {
        $this->{$name} = $value;
    }

    public function __get($name)
    {
        return $this->{$name};
    }

    public function __isset($name)
    {
        return property_exists($this, $name);
    }

    public function addLog($url, $sent, $received)
    {
        $this->debug_logs[] = array(
            "url" => $url,
            "data_string" => array(
                "sent" => print_r($sent, true),
                "received" => print_r($received, true)
            ),
            "data_array" => array(
                "sent" => $sent,
                "received" => $received
            )
        );
    }

    public function getLogs($order = "all")
    {
        if ($this->active && $this->debug_logs && is_array($this->debug_logs)) {
            switch ($order) {
                case "first" :
                    return $this->debug_logs[0];
                case "last" :
                    return end($this->debug_logs);
                case "all":
                default:
                    return $this->debug_logs;
            }
        } else {
            return false;
        }
    }
}
