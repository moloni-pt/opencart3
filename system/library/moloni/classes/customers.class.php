<?php
/* Moloni
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */
namespace moloni;

class customers
{

    public function __construct(\moloni $moloni)
    {
        $this->moloni = $moloni;
    }

    public function getByVat($vat_number, $exact = false, $company_id = false)
    {
        $values = array(
            "company_id" => ($company_id ? $company_id : $this->moloni->company_id),
            "vat" => $vat_number,
            "exact" => $exact ? "1" : "0"
        );

        $result = $this->moloni->connection->curl("customers/getByVat", $values);
        if (is_array($result) && isset($result[0]['customer_id'])) {
            return $result;
        } else {
            return false;
        }
    }

    public function getBySearch($search, $exact = false, $company_id = false)
    {
        $values = array(
            "company_id" => ($company_id ? $company_id : $this->moloni->company_id),
            "search" => $search,
            "exact" => $exact ? "1" : "0"
        );

        $result = $this->moloni->connection->curl("customers/getBySearch", $values);
        if (is_array($result) && isset($result[0]['customer_id'])) {
            return $result;
        } else {
            return false;
        }
    }

    public function count()
    {

    }
}
