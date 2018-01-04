<?php
/* Moloni
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */
namespace moloni;

class countries
{

    public function __construct(\moloni $moloni)
    {
        $this->moloni = $moloni;
    }

    public function getAll()
    {
        $result = $this->moloni->connection->curl("countries/getAll");
        if (is_array($result) && isset($result[0]['country_id'])) {
            return $result;
        } else {
            $this->moloni->errors->throwError("Erro ao obter a listagem de países", $result[0], __CLASS__ . "/" . __FUNCTION__);
            return false;
        }
    }
}
