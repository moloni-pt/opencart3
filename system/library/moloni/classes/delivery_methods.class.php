<?php
/* Moloni
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */
namespace moloni;

class delivery_methods
{

    public function __construct(\moloni $moloni)
    {
        $this->moloni = $moloni;
    }

    public function getAll($company_id = false)
    {
        $values = array("company_id" => $company_id ? $company_id : $this->moloni->company_id);
        $result = $this->moloni->connection->curl("deliveryMethods/getAll", $values);
        if (is_array($result) && isset($result[0]['delivery_method_id'])) {
            return $result;
        } else {
            return false;
        }
    }

    public function insert($input, $company_id = false)
    {
        $values = array();
        $values['company_id'] = $company_id ? $company_id : $this->moloni->company_id;
        $values['name'] = $input['name'];

        $result = $this->moloni->connection->curl("deliveryMethods/insert", $values);
        if (is_array($result) && isset($result['delivery_method_id'])) {
            return $result;
        } else {
            $this->moloni->errors->throwError("Erro ao inserir método de transporte", $result[0], __CLASS__ . "/" . __FUNCTION__, $result, $values);
            return false;
        }
    }
}
