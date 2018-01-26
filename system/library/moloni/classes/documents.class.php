<?php
/* Moloni
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */
namespace moloni;

class documents
{

    public function __construct(\moloni $moloni)
    {
        $this->moloni = $moloni;
    }

    public function insert($values = array(), $company_id = false)
    {
        $values["company_id"] = $company_id ? $company_id : $this->moloni->company_id;

        $result = $this->moloni->connection->curl($this->moloni->documentType . "/insert", $values);
        if (is_array($result) && isset($result['document_id'])) {
            return $result;
        } else {
            $this->moloni->errors->throwError("Erro ao inserir documento", $result[0], __CLASS__ . "/" . __FUNCTION__, $result, $values);
            return false;
        }
    }
}
