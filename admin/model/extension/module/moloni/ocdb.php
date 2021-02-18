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

    public function getDocumentFromOrderId($order_id)
    {
        $sql = "SELECT * FROM `" . DB_PREFIX . "moloni_documents` md WHERE md.order_id = '" . $order_id . "'";
        $query = $this->db->query($sql);
        $result = $query->row;

        return $result;
    }

    public function getOrderById($order_id)
    {
        $sql = "SELECT * FROM `" . DB_PREFIX . "order` o LEFT JOIN `" . DB_PREFIX . "moloni_documents` MD ON o.order_id = MD.order_id WHERE o.order_id = '" . $order_id . "'";
        $query = $this->db->query($sql);
        $result = $query->row;

        return $result;
    }

    public function getOrdersAll($order_statuses = false, $order_since = false, $store = false, $order_ids = false, $on_moloni = false)
    {

        $sql = "SELECT o.*, MD.invoice_id, MD.metadata, "
            . "(SELECT os.name FROM " . DB_PREFIX . "order_status os WHERE os.order_status_id = o.order_status_id AND os.language_id = '" . (int) $this->language_id . "') AS order_status "
            . " FROM `" . DB_PREFIX . "order` o LEFT JOIN `" . DB_PREFIX . "moloni_documents` MD ON o.order_id = MD.order_id WHERE o.order_id IS NOT NULL ";

        if ($order_ids && count($order_ids) > 0) {
            $sql .= " AND o.order_id IN (" . implode(',', $order_ids) . ")";
        }

        if (is_array($order_statuses) && !empty($order_statuses)) {
            $sql .= " AND o.order_status_id IN (" . implode(',', $order_statuses) . ")";
        }

        if (is_array($order_since) && !empty($order_since)) {
            $sql .= " AND o.date_added > '" . $order_since . "' ";
        }

        if ($store) {
            $sql .= " AND o.store_id = '" . $store . "'";
        }

        if ($on_moloni) {
            $sql .= " AND MD.invoice_id IS NOT NULL";
        } else {
            $sql .= " AND MD.invoice_id IS NULL";
        }

        $sql .= " ORDER BY o.order_id DESC";

        $query = $this->db->query($sql);
        $result = $query->rows;

        if (!$on_moloni) {
            foreach ($result as &$order) {
                $order['order_status'] = $order['order_status'] ? $order['order_status'] : $this->language->get('Sem categoria');
                $order['total_formated'] = $this->currency->format($order['total'], $order['currency_code'], $order['currency_value']);
                $order['date_added'] = date($this->language->get('date_format_short'), strtotime($order['date_added']));
                $order['moloni_create'] = $this->url->link('extension/module/moloni/invoice', 'user_token=' . $this->session->data['user_token'] . '&order_id=' . $order['order_id'], true);
            }
        }

        return $result;
    }

    public function getCustomFieldsAll()
    {
        $sql = "SELECT cf.custom_field_id, "
            . "(SELECT cfd.name FROM " . DB_PREFIX . "custom_field_description cfd WHERE cfd.custom_field_id = cf.custom_field_id LIMIT 1) AS name "
            . " FROM `" . DB_PREFIX . "custom_field` cf";

        $query = $this->db->query($sql);
        $result = $query->rows;

        return $result;
    }

    public function getOptionMoloniReference($product_option_value_id)
    {
        $sql = "SELECT moloni_reference FROM " . DB_PREFIX . "product_option_value WHERE product_option_value_id = '" . $product_option_value_id . "'";
        $query = $this->db->query($sql);
        $result = $query->row;

        return isset($result['moloni_reference']) ? $result['moloni_reference'] : '';
    }

    public function updateOptionMoloniReference($value, $option_id)
    {
        $this->db->query("UPDATE " . DB_PREFIX . "product_option_value SET moloni_reference = '" . $value . "' WHERE product_option_value_id = '" . $option_id . "'");
    }

    public function getClientGeoZone($country_id, $payment_zone_id = 0)
    {
        $sql = "SELECT * FROM " . DB_PREFIX . "zone_to_geo_zone WHERE country_id = '" . $country_id . "' AND (zone_id = '" . $payment_zone_id . "' OR zone_id = 0)";
        $query = $this->db->query($sql);
        $result = $query->rows;

        return $result;
    }

    public function getTaxRules($tax_class_id)
    {
        $sql = "SELECT * FROM " . DB_PREFIX . "tax_rule WHERE tax_class_id = '" . $tax_class_id . "' ORDER BY priority ASC";
        $query = $this->db->query($sql);
        $result = $query->rows;

        return $result;
    }

    public function getTaxRate($tax_rate_id, $geo_zone_id)
    {
        $taxRate = 0;
        foreach($geo_zone_id as $geo_zone){
            $sql = "SELECT * FROM " . DB_PREFIX . "tax_rate WHERE tax_rate_id = '" . $tax_rate_id . "' AND geo_zone_id = " . $geo_zone['geo_zone_id'] . " ";
            $query = $this->db->query($sql);
            $result = $query->row;
            if(!empty($result)){
                if($geo_zone['zone_id'] != 0){
                    $taxRate = $result;
                } elseif (empty($taxRate)){
                    $taxRate = $result;
                }
            }
        }

        return $taxRate;
    }

    public function getShippingDiscount($code)
    {
        $sql = "SELECT * FROM " . DB_PREFIX . "coupon WHERE code LIKE '" . $code . "'";
        $query = $this->db->query($sql);
        $result = $query->row;

        return $result['shipping'] == 1 ? true : false;
    }

    public function getCategory($category_id)
    {
        $query = $this->db->query("SELECT DISTINCT *, (SELECT GROUP_CONCAT(cd1.name ORDER BY level SEPARATOR '&nbsp;&nbsp;&gt;&nbsp;&nbsp;') FROM " . DB_PREFIX . "category_path cp LEFT JOIN " . DB_PREFIX . "category_description cd1 ON (cp.path_id = cd1.category_id AND cp.category_id != cp.path_id) WHERE cp.category_id = c.category_id AND cd1.language_id = '" . (int) $this->language_id . "' GROUP BY cp.category_id) AS path FROM " . DB_PREFIX . "category c LEFT JOIN " . DB_PREFIX . "category_description cd2 ON (c.category_id = cd2.category_id) WHERE c.category_id = '" . (int) $category_id . "' AND cd2.language_id = '" . (int) $this->language_id . "'");
        return $query->row;
    }

    public function getCurrencyValue($currency_code)
    {
        $query = $this->db->query("SELECT * FROM " . DB_PREFIX . "currency WHERE code = '" . $currency_code . "'");
        $result = $query->row;

        return $result['value'];
    }

    public function getStoreCurrency($store_id = 0)
    {
        $query = $this->db->query("SELECT * FROM " . DB_PREFIX . "setting WHERE " . DB_PREFIX . "setting.key = 'config_currency' AND store_id = '" . $store_id . "'");
        $result = $query->row;
        return $result['value'];
    }

    public function getStoreLocation($select = 'all')
    {
        $sql = "SELECT * FROM " . DB_PREFIX . "location " . ($select == 'all' ? "" : " WHERE location_id = " . $select);
        $query = $this->db->query($sql);
        $result = $query->rows;

        return $result;
    }

    public function setDocumentInserted($values)
    {
        $sql = "INSERT INTO " . DB_PREFIX . "moloni_documents SET";
        foreach ($values as $key => $value) {
            $sql .= " " . $key . " = '" . $value . "', ";
        }

        $sql = rtrim($sql, ", ");

        $this->db->query($sql);
    }
}
