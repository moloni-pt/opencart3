<?php
/* Moloni
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */
namespace moloni;

class categories
{

    private $category_list = false;

    public function __construct(\moloni $moloni)
    {
        $this->moloni = $moloni;
    }

    public function insert($values, $company_id = false)
    {
        $values = array(
            "company_id" => ($company_id ? $company_id : $this->moloni->company_id),
            "name" => $values['name'],
            "parent_id" => $values['parent_id'],
            "description" => "",
        );

        $result = $this->moloni->connection->curl("productCategories/insert", $values);

        if (isset($result['valid']) && $result['valid'] == 1) {
            $this->category_list[] = array(
                "category_id" => "",
                "name" => $values['name'],
                "parent_id" => $values['parent_id']);
        }

        return $result;
    }

    public function getAll($parent_id = "0", $company_id = false)
    {
        $values = array(
            "company_id" => ($company_id ? $company_id : $this->moloni->company_id),
            "parent_id" => $parent_id,
        );

        $result = $this->moloni->connection->curl("productCategories/getAll", $values);
        return $result;
    }

    public function getAllCached()
    {
        return $this->category_list;
    }

    public function getAllRecursive($category_id)
    {
        $categories = $this->getAll($category_id);
        foreach ($categories as $category) {
            $this->category_list[] = $category;
            if ($category["num_categories"] > 0) {
                $this->getAllRecursive($category['category_id']);
            }
        }

        return $this->category_list;
    }
}
