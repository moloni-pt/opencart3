<?php
/* Moloni
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */
namespace moloni;

class debug
{

    public $active = true;
    private $debug_logs = array();

    function __construct(\moloni $moloni)
    {
        $this->moloni = $moloni;
        return true;
    }

    public function __set($name, $value)
    {
        $this->{$name} = $value;
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
                    $aux = end($this->debug_logs);
                    return $aux;
                case "all":
                default:
                    return $this->debug_logs;
            }
        } else {
            return false;
        }
    }
}
