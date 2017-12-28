<?php
/* Moloni
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */
namespace moloni;

class taxes
{

    public function __construct(\moloni $moloni)
    {
        $this->moloni = $moloni;
    }

    public function getAll()
    {
        $values = array("company_id" => $this->moloni->company_id);
        $result = $this->moloni->connection->curl("taxes/getAll", $values);
        if (is_array($result) && isset($result[0]['tax_id'])) {
            return $result;
        } else {
            $this->moloni->errors->throwError("Não tem taxas de IVA disponíveis", "Não tem taxas de IVA disponíveis para serem usadas", __CLASS__ . "/" . __FUNCTION__);
            return false;
        }
    }

    public function getExemptions()
    {
        $result = $this->moloni->connection->curl("taxExemptions/getAll");
        if (is_array($result) && isset($result[0]['code'])) {
            return $result;
        } else {
            $this->moloni->errors->throwError("Não foram encontradas razões de isenção", "Não foram encontradas razões de isenção", __CLASS__ . "/" . __FUNCTION__);
            return false;
        }
    }
}
