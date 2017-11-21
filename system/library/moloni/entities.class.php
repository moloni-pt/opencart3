<?php
/* Moloni
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */
namespace moloni;

class entities
{

    private $libs = array(
        "customers" => "entities/customers.class.php",
        "alternate" => "alternate.class.php",
        "suppliers" => "suppliers.class.php",
        "salesmen" => "salesmen.class.php"
    );

    function __construct(\moloni $moloni)
    {
        $this->moloni = $moloni;
        echo __CLASS__;

        return true;
    }

    public function __get($name)
    {
        echo $name;
        if (!isset($this->{$name})) {
            require($this->libs[$name]);
            $class = 'moloni\\' . $name;
            $this->{$name} = new $class($this->moloni);
        }
        return $this->{$name};
    }

    public function loadLibraries()
    {
        foreach ($this->libraries as $name => $library) {
            require_once("entities/" . $library);
            $class = 'moloni\\' . $name;
            $this->{$name} = new $class($this->moloni);
        }
    }
}
