<?php
/* Moloni
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */
namespace moloni;

class payment_methods
{

    public function __construct(\moloni $moloni)
    {
        $this->moloni = $moloni;
    }

    public function getAll($company_id = false)
    {
        $values = array("company_id" => $company_id ? $company_id : $this->moloni->company_id);
        $result = $this->moloni->connection->curl("paymentMethods/getAll", $values);
        if (is_array($result) && isset($result[0]['payment_method_id'])) {
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
        $values['is_numerary'] = isset($input['is_numerary']) ? $input['is_numerary'] : 0;

        $result = $this->moloni->connection->curl("paymentMethods/insert", $values);
        if (is_array($result) && isset($result['payment_method_id'])) {
            return $result;
        } else {
            $this->moloni->errors->throwError("Erro ao inserir método de pagamento", $result[0], __CLASS__ . "/" . __FUNCTION__, $result, $values);
            return false;
        }
    }
}
