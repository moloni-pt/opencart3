<?php
/* Moloni
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */
namespace moloni;

class errors
{

    private $error_log = array();

    public function __construct(\ModelExtensionModuleMoloniMoloni $moloni)
    {
        $this->moloni = $moloni;
        return false;
    }

    public function throwError($title, $message, $where, $received = false, $sent = false)
    {
        $this->error_log[] = array(
            "title" => $title,
            "message" => $this->translateMessage($message),
            "where" => $where,
            "values" => array(
                "received" => $received,
                "sent" => $sent
            )
        );
    }

    // @params $order all|first|last
    public function getError($order = "all")
    {
        if ($this->error_log && is_array($this->error_log)) {
            switch ($order) {
                case "first" :
                    return $this->error_log[0];
                case "last" :
                    $aux = end($this->error_log);
                    return $aux;
                case "all":
                default:
                    return $this->error_log;
            }
        } else {
            return false;
        }
    }

    private function translateMessage($string)
    {
        switch ($string) {
            case "" :
                break;
        }

        return $string;
    }
}
