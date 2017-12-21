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
        $sql = "SELECT DISTINCT * FROM `" . DB_PREFIX . "moloni` LIMIT 1";
        $query = $this->db->query($sql);
        $this->logs[] = array("where" => __FUNCTION__, "query" => $sql, "result" => $query->row);
        return $query->row;
    }

    public function qInsertMoloniTokens($access_token, $refresh_token, $expire_date)
    {
        $query = $this->db->query("INSERT INTO `" . DB_PREFIX . "moloni`(access_token, refresh_token, expire_date) VALUES('" . $access_token . "', '" . $refresh_token . "', '" . $expire_date . "')");
        $this->logs[] = array("where" => __FUNCTION__, "query" => $query);
        return $this->qGetMoloniTokens;
    }

    public function qDeleteMoloniTokens()
    {
        $query = $this->db->query("TRUNCATE TABLE `" . DB_PREFIX . "moloni`");
        $this->logs[] = array("where" => __FUNCTION__, "query" => $query);
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

    public function qExistsSetting($label, $store_id, $company_id)
    {
        $query = "SELECT * FROM " . DB_PREFIX . "moloni_settings WHERE label LIKE '" . $label . "' AND store_id = '" . $store_id . "' AND company_id = '" . $company_id . "'";
        $result = $this->db->query($query);
        return ($result->num_rows > "0" ? $result->row : false);
    }

    public function getMoloniSettings($company_id, $store_id = 0)
    {
        $company_settings = $this->cache->get("moloni_settings" . $company_id . $store_id);
        if (!$company_settings) {
            $sql = "SELECT * FROM " . DB_PREFIX . "moloni_settings WHERE company_id = '" . $company_id . "' AND store_id = '" . $store_id . "' ";
            $result = $this->db->query($sql);
            $company_settings = $result->rows;
            $this->cache->set("moloni_settings" . $company_id . $store_id, $company_settings);
        }
        return $company_settings;
    }

    public function qUpdateMoloniSetting($label, $store_id, $company_id, $value)
    {
        $this->cache->delete("moloni_settings" . $company_id . $store_id);
        $sql = "UPDATE `" . DB_PREFIX . "moloni_settings` SET value = '" . $value . "' WHERE label LIKE '" . $label . "' AND store_id = '" . $store_id . "' AND company_id = '" . $company_id . "'";
        $this->db->query($sql);
        return true;
    }

    public function qInsertMoloniSetting($label, $store_id, $company_id, $value)
    {
        $this->cache->delete("moloni_settings" . $company_id . $store_id);
        $sql = "INSERT INTO `" . DB_PREFIX . "moloni_settings`(label, store_id, company_id, value) VALUES('" . $label . "', '" . $store_id . "', '" . $company_id . "', '" . $value . "')";
        $this->db->query($sql);
        return true;
    }
}
