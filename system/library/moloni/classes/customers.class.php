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
            'company_id' => ($company_id ? $company_id : $this->moloni->company_id),
            'vat' => $vat_number,
            'exact' => $exact ? '1' : '0'
        );

        $result = $this->moloni->connection->curl('customers/getByVat', $values);
        if (is_array($result) && isset($result[0]['customer_id'])) {
            return $result;
        }

        return false;
    }

    public function getBySearch($search, $exact = false, $company_id = false)
    {
        $values = array(
            'company_id' => ($company_id ? $company_id : $this->moloni->company_id),
            'search' => $search,
            'exact' => $exact ? '1' : '0'
        );

        $result = $this->moloni->connection->curl('customers/getBySearch', $values);
        if (is_array($result) && isset($result[0]['customer_id'])) {
            return $result;
        }

        return false;
    }

    public function getLastNumber($company_id = false)
    {
        $values = array();
        $values['company_id'] = $company_id ? $company_id : $this->moloni->company_id;

        $result = $this->moloni->connection->curl('customers/getLastNumber', $values);
        if (is_array($result) && isset($result['number'])) {
            return $result;
        }

        return '1';
    }

    public function getByNumber($number = '')
    {
        $values = [
            'number' => "$number%",
            'company_id' => $this->moloni->company_id,
            'order_by_field' => 'customer_id',
            'order_by_ordering' => 'desc',
            'qty' => 1,
            'exact' => 1,
        ];

        $result = $this->moloni->connection->curl('customers/getByNumber', $values);

        if (is_array($result) && isset($result[0]['number'])) {
            return $result[0];
        }

        return [];
    }

    public function getNextNumber($company_id = false)
    {
        $values = array();
        $values['company_id'] = $company_id ? $company_id : $this->moloni->company_id;

        $result = $this->moloni->connection->curl('customers/getNextNumber', $values);
        if (is_array($result) && isset($result['number'])) {
            return $result;
        }

        return '1';
    }

    public function insert($input, $company_id = false)
    {

        $values = array();
        $values['company_id'] = $company_id ? $company_id : $this->moloni->company_id;

        $values['number'] = $input['number'];
        $values['vat'] = isset($input['vat']) ? $input['vat'] : '999999990';

        $values['name'] = $input['name'];
        $values['address'] = $input['address'];
        $values['zip_code'] = $input['zip_code'];
        $values['city'] = $input['city'];
        $values['maturity_date_id'] = $input['maturity_date_id'];
        $values['payment_method_id'] = $input['payment_method_id'];
        $values['delivery_method_id'] = isset($input['delivery_method_id']) ? $input['delivery_method_id'] : 0;
        $values['country_id'] = $input['country_id'];
        $values['language_id'] = $input['language_id'];
        $values['copies'] = $input['copies'];

        $values['email'] = isset($input['email']) ? $input['email'] : '';
        $values['phone'] = isset($input['phone']) ? $input['phone'] : '';
        $values['website'] = isset($input['website']) ? $input['website'] : '';
        $values['fax'] = isset($input['fax']) ? $input['fax'] : '';
        $values['contact_name'] = isset($input['contact_name']) ? $input['contact_name'] : '';
        $values['contact_email'] = isset($input['contact_email']) ? $input['contact_email'] : '';
        $values['contact_phone'] = isset($input['contact_phone']) ? $input['contact_phone'] : '';
        $values['contact_email'] = isset($input['contact_email']) ? $input['contact_email'] : '';
        $values['salesman_id'] = isset($input['salesman_id']) ? $input['salesman_id'] : '0';
        $values['payment_day'] = isset($input['payment_day']) ? $input['payment_day'] : '0';
        $values['discount'] = isset($input['discount']) ? $input['discount'] : '0';
        $values['credit_limit'] = isset($input['credit_limit']) ? $input['credit_limit'] : '0';
        $values['notes'] = isset($input['notes']) ? $input['notes'] : '';
        $values['field_notes'] = isset($input['field_notes']) ? $input['field_notes'] : '';


        $result = $this->moloni->connection->curl('customers/insert', $values);
        if (is_array($result) && isset($result['customer_id'])) {
            return $result;
        }

        if (isset($result[0])) {
            $this->moloni->errors->throwError('Erro ao inserir cliente', $result[0], __CLASS__ . '/' . __FUNCTION__);
        } else {
            $this->moloni->errors->throwError('Erro ao inserir cliente', 'Referência repetida', __CLASS__ . '/' . __FUNCTION__);
        }

        return false;
    }

    public function update($input, $company_id = false)
    {

        $values = array();

        $values['company_id'] = $company_id ? $company_id : $this->moloni->company_id;
        $values['customer_id'] = $input['customer_id'];

        if (isset($input['name'])) {
            $values['name'] = $input['name'];
        }

        if (isset($input['address'])) {
            $values['address'] = $input['address'];
        }

        if (isset($input['zip_code'])) {
            $values['zip_code'] = $input['zip_code'];
        }

        if (isset($input['city'])) {
            $values['city'] = $input['city'];
        }


        if (isset($input['contact_name'])) {
            $values['contact_name'] = $input['contact_name'];
        }

        if (isset($input['contact_email'])) {
            $values['contact_email'] = $input['contact_email'];
        }

        if (isset($input['contact_phone'])) {
            $values['contact_phone'] = $input['contact_phone'];
        }

        if (isset($input['contact_email'])) {
            $values['contact_email'] = $input['contact_email'];
        }

        if (isset($input['email'])) {
            $values['email'] = $input['email'];
        }

        if (isset($input['phone'])) {
            $values['phone'] = $input['phone'];
        }

        if (isset($input['website'])) {
            $values['website'] = $input['website'];
        }

        if (isset($input['fax'])) {
            $values['fax'] = $input['fax'];
        }

        if (isset($input['maturity_date_id'])) {
            $values['maturity_date_id'] = $input['maturity_date_id'];
        }

        if (isset($input['payment_method_id'])) {
            $values['payment_method_id'] = $input['payment_method_id'];
        }

        if (isset($input['delivery_method_id'])) {
            $values['delivery_method_id'] = $input['delivery_method_id'];
        }

        if (isset($input['country_id'])) {
            $values['country_id'] = $input['country_id'];
        }

        if (isset($input['language_id'])) {
            $values['language_id'] = $input['language_id'];
        }

        if (isset($input['copies']) && is_array($input['copies'])) {
            $values['copies'] = $input['copies'];
        }

        if (isset($input['notes'])) {
            $values['notes'] = $input['notes'];
        }

        if (isset($input['salesman_id'])) {
            $values['salesman_id'] = $input['salesman_id'];
        }

        if (isset($input['payment_day'])) {
            $values['payment_day'] = $input['payment_day'];
        }

        if (isset($input['discount'])) {
            $values['discount'] = $input['discount'];
        }

        if (isset($input['credit_limit'])) {
            $values['credit_limit'] = $input['credit_limit'];
        }

        if (isset($input['field_notes'])) {
            $values['field_notes'] = $input['field_notes'];
        }

        $result = $this->moloni->connection->curl('customers/update', $values);
        if (is_array($result) && isset($result['customer_id'])) {
            return $result;
        } else {
            $this->moloni->errors->throwError('Erro ao actualizar cliente', $result[0], __CLASS__ . '/' . __FUNCTION__);
            return false;
        }
    }

    public function count()
    {

    }
}
