<?php
/* Moloni
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */
namespace moloni;

class alternate
{

    public function __construct(\moloni $moloni)
    {
        $this->moloni = $moloni;
    }

    /**
     * @name getAll
     * @param array $values['customer_id']
     */
    public function getAll($input)
    {
        $values['company_id'] = $this->moloni->company_id;
        $values['customer_id'] = $input['customer_id'];

        $result = $this->moloni->connection->curl("customerAlternateAddresses/getAll", $values);
        return $result;
    }

    /**
     * @name insert
     * @param array $values['customer_id']
     */
    public function insert($input)
    {
        $values['company_id'] = $this->moloni->company_id;
        $values['customer_id'] = $input['customer_id'];
        $values['designation'] = $input['designation'];
        $values['code'] = $input['code'];
        $values['address'] = $input['address'];
        $values['city'] = $input['city'];
        $values['zip_code'] = $input['zip_code'];
        $values['country_id'] = $input['country_id'];
        $values['email'] = $input['email'];
        $values['phone'] = $input['phone'];
        $values['country_id'] = $input['country_id'];

        $result = $this->moloni->connection->curl("customerAlternateAddresses/insert", $values);

        if (isset($result['address_id'])) {
            return $result;
        } else {
            $this->moloni->errors->throwError("Erro ao inserir morada alternativa", "customerAlternateAddresses/insert", "refresh_token");
        }
    }

    /**
     * @name delete
     * @param array $values['customer_id']
     */
    public function delete($input)
    {
        $values['company_id'] = $this->moloni->company_id;
        $values['customer_id'] = $input['customer_id'];
        $values['address_id'] = $input['address_id'];

        $result = $this->moloni->connection->curl("customerAlternateAddresses/delete", $values);

        if (isset($result['address_id'])) {
            return $result;
        } else {
            $this->moloni->errors->throwError("Erro ao apagar morada alternativa", __CLASS__ . "/" . __FUNCTION__, "refresh_token");
        }
    }

    /**
     * @name insert
     * @param array $values['customer_id']
     */
    public function update($input)
    {
        $values['company_id'] = $this->moloni->company_id;
        if (isset($input['customer_id'])) {
            $values['customer_id'] = $input['customer_id'];
        }
        if (isset($input['designation'])) {
            $values['designation'] = $input['designation'];
        }
        if (isset($input['code'])) {
            $values['code'] = $input['code'];
        }
        if (isset($input['address'])) {
            $values['address'] = $input['address'];
        }
        if (isset($input['city'])) {
            $values['city'] = $input['city'];
        }
        if (isset($input['zip_code'])) {
            $values['zip_code'] = $input['zip_code'];
        }
        if (isset($input['country_id'])) {
            $values['country_id'] = $input['country_id'];
        }
        if (isset($input['email'])) {
            $values['email'] = $input['email'];
        }
        if (isset($input['phone'])) {
            $values['phone'] = $input['phone'];
        }

        $result = $this->moloni->connection->curl("customerAlternateAddresses/insert", $values);

        if (isset($result['address_id'])) {
            return $result;
        } else {
            $this->moloni->errors->throwError("Erro ao inserir morada alternativa", "customerAlternateAddresses/insert", "refresh_token");
        }
    }
}
