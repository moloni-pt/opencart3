<?php
/* Moloni
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */
namespace moloni;

class products
{

    public function __construct(\moloni $moloni)
    {
        $this->moloni = $moloni;
    }

    public function getByReference($moloni_reference, $exact = true, $company_id = false)
    {
        $values = array(
            "company_id" => ($company_id ? $company_id : $this->moloni->company_id),
            "reference" => $moloni_reference,
            "exact" => $exact ? "1" : "0"
        );

        $result = $this->moloni->connection->curl("products/getByReference", $values);
        print_r($result);
        if (is_array($result) && isset($result[0]['product_id'])) {
            return $result;
        } else {
            return false;
        }
    }
}
