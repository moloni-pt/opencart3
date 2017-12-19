<?php
/* Moloni -
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

class ModelExtensionModuleMoloniOcdb extends Model
{

    private $logs = array();

    public function qGetMoloniTokens()
    {
        $query = $this->db->query("SELECT DISTINCT * FROM `" . DB_PREFIX . "moloni` LIMIT 1");
        $row = $query->row;
        $this->logs[] = array("where" => __FUNCTION__, "query" => $query, "result" => $row);
        return $row;
    }

    public function qInsertMoloniTokens($access_token, $refresh_token, $expire_date)
    {
        $query = $this->db->query("INSERT INTO `" . DB_PREFIX . "moloni`(access_token, refresh_token, expire_date) VALUES('" . $access_token . "', '" . $refresh_token . "', '" . $expire_date . "')");
        $this->logs[] = array("where" => __FUNCTION__, "query" => $query);
        return $this->qGetMoloniTokens;
    }

    public function qUpdateMoloniTokens($access_token, $refresh_token, $expire_date)
    {
        $query = $this->db->query("UPDATE `" . DB_PREFIX . "moloni` SET access_token = '" . $access_token . "', refresh_token = '" . $refresh_token . "', expire_date = '" . $expire_date . "'");
        $this->logs[] = array("where" => __FUNCTION__, "query" => $query);
        return $this->qGetMoloniTokens;
    }

    public function qUpdateMoloniCompany($company_id)
    {
        $this->db->query("UPDATE `" . DB_PREFIX . "moloni` SET company_id = '" . $company_id . "'");
    }

    public function getStores($data = array())
    {
        $store_data = $this->cache->get('store');
        if (!$store_data) {
            $query = $this->db->query("SELECT * FROM " . DB_PREFIX . "store ORDER BY url");
            $store_data = $query->rows;
            $this->cache->set('store', $store_data);
        }
        return $store_data;
    }

    public function getTotalStores()
    {
        $query = $this->db->query("SELECT COUNT(*) AS total FROM " . DB_PREFIX . "store");
        return $query->row['total'];
    }
}
