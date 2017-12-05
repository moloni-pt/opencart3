<?php
/* Moloni
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */
namespace moloni;

class companies
{

    public function __construct(\moloni $moloni)
    {
        $this->moloni = $moloni;
    }

    public function getAll()
    {
        $result = $this->moloni->connection->curl("companies/getAll");
        if (is_array($result) && isset($result[0]['company_id'])) {
            print_r($result);
            return $result;
        } else {
            $this->moloni->errors->throwError("Não tem empresas disponíveis", "Não tem empresas disponíveis para serem usadas", __CLASS__ . "/" . __FUNCTION__);
            return false;
        }
    }
}
