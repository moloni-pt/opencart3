<?php
/* Moloni
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */
namespace moloni;

class settings
{

    public function __construct(\ModelExtensionModuleMoloniMoloni $moloni)
    {

    }

    public function getValue()
    {
        echo $moloni->customer->getValue();
    }
}
