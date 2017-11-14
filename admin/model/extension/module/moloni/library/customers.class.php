<?php
/* Moloni
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */
namespace moloni;

class customer
{

    public $teste = 10;

    function __construct(\ModelExtensionModuleMoloniMoloni $moloni)
    {
        echo __CLASS__ . __FUNCTION__;
        echo $this->teste . "<br>2";
        $this->teste = 20;
        echo $this->teste . "<br>";
        $this->teste = 30;
    }

    public function __invoke($id)
    {
        echo $id;
    }

    public function __set($name, $value)
    {

    }

    function getValue()
    {
        echo $this->teste;
    }
}
