<?php
/* Moloni
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */
namespace moloni;

class measurements
{

    public function __construct(\moloni $moloni)
    {
        $this->moloni = $moloni;
    }

    public function getAll($company_id = false)
    {
        $values = array("company_id" => $company_id ? $company_id : $this->moloni->company_id);
        $result = $this->moloni->connection->curl("measurementUnits/getAll", $values);
        if (is_array($result) && isset($result[0]['unit_id'])) {
            return $result;
        } else {
            return false;
        }
    }
}
