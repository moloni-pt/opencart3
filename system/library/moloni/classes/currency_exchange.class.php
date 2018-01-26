<?php
/* Moloni
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */
namespace moloni;

class currency_exchange
{

    public function __construct(\moloni $moloni)
    {
        $this->moloni = $moloni;
    }

    public function getAll()
    {
        $result = $this->moloni->connection->curl("currencyExchange/getAll");
        if (is_array($result) && isset($result[0]['currency_exchange_id'])) {
            return $result;
        } else {
            $this->moloni->errors->throwError("Erro ao obter a taxas de câmbio", $result[0], __CLASS__ . "/" . __FUNCTION__);
            return false;
        }
    }
}
