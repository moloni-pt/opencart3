<?php
/* Moloni
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */
namespace moloni;

class entities
{

    private $libraries = array(
        "customers" => "customers.class.php"
    );

    function __construct(\moloni $moloni)
    {
        $this->moloni = $moloni;
        echo __CLASS__;

        $this->loadLibraries();
        return true;
    }

    public function loadLibraries()
    {
        foreach ($this->libraries as $name => $library) {
            try {
                require_once("entities/" . $library);
                $class = 'moloni\\' . $name;
                $this->{$name} = new $class($this->moloni);
            } catch (Exception $e) {
                echo 'Caught exception: ', $e->getMessage(), "\n";
            }
        }
    }
}
