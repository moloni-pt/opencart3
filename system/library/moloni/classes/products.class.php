<?php
/* Moloni
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */
namespace moloni;

class products
{

    public function __construct(\moloni $moloni)
    {
        $this->moloni = $moloni;
    }

    public function getByReference($moloni_reference, $exact = true, $company_id = false)
    {
        $values = array(
            "company_id" => ($company_id ? $company_id : $this->moloni->company_id),
            "reference" => $moloni_reference,
            "exact" => $exact ? "1" : "0"
        );

        $result = $this->moloni->connection->curl("products/getByReference", $values);
        if (is_array($result) && isset($result[0]['product_id'])) {
            return $result;
        } else {
            return false;
        }
    }

    public function insert($input, $company_id = false)
    {
        $values["company_id"] = ($company_id ? $company_id : $this->moloni->company_id);

        $values["category_id"] = $input['category_id'];
        $values["type"] = $input['type'];
        $values["name"] = $input['name'];
        $values["reference"] = $input['reference'];
        $values["price"] = $input['price'];
        $values["unit_id"] = $input['unit_id'];
        $values["has_stock"] = $input['has_stock'];

        $values["summary"] = isset($input['summary']) ? $input['summary'] : "";
        $values["ean"] = isset($input['ean']) ? $input['ean'] : "";
        $values["stock"] = isset($input['stock']) ? $input['stock'] : "";
        $values["pos_favorite"] = isset($input['pos_favorite']) ? $input['pos_favorite'] : "";
        $values["at_product_category"] = isset($input['at_product_category']) ? $input['at_product_category'] : "";
        $values["exemption_reason"] = isset($input['exemption_reason']) ? $input['exemption_reason'] : "";

        $values["taxes"] = isset($input['taxes']) && is_array($input['taxes']) ? $input['taxes'] : "";
        $values["suppliers"] = isset($input['suppliers']) && is_array($input['suppliers']) ? $input['suppliers'] : "";
        $values["properties"] = isset($input['properties']) && is_array($input['properties']) ? $input['properties'] : "";
        $values["warehouses"] = isset($input['warehouses']) && is_array($input['warehouses']) ? $input['warehouses'] : "";

        $result = $this->moloni->connection->curl("products/insert", $values);
        if (is_array($result) && isset($result['product_id'])) {
            return $result;
        } else {
            $this->moloni->errors->throwError("Erro ao inserir artigo " . $values["reference"] . " - " . $values["name"], $result[0], __CLASS__ . "/" . __FUNCTION__);
            return false;
        }
    }

    public function getModifiedSince($company_id = false, $offset = 0, $lastmodified = 0)
    {
        if(empty($lastmodified)){
            $lastmodifiedGMT = date("Y-m-d 01:00:00", strtotime("-7 days"));
        } else {
            $lastmodifiedGMT = date("Y-m-d 01:00:00", strtotime($lastmodified));
        }

        $values = array(
            "company_id" => ($company_id ? $company_id : $this->moloni->company_id),
            "offset" => $offset,
            'lastmodified' => $lastmodifiedGMT
        );

        return $this->moloni->connection->curl("products/getModifiedSince", $values);
    }

    public function getProductCategoryTree($product_id, $company_id = false)
    {
        $values = array(
            "company_id" => ($company_id ? $company_id : $this->moloni->company_id),
            'product_id' => $product_id
        );

        return $this->moloni->connection->curl("products/getCategoryTree", $values);
    }
}
