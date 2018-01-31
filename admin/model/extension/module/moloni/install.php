<?php
/* Moloni

 * To change this license header, choose License Headers in Project Properties.

 * To change this template file, choose Tools | Templates

 * and open the template in the editor.

 */

class ModelExtensionModuleMoloniInstall extends Model
{

    public function createTables()
    {
        $this->db->query("
			CREATE TABLE IF NOT EXISTS `" . DB_PREFIX . "moloni` (
                    `id` int(2) NOT NULL AUTO_INCREMENT,
                    `access_token` varchar(200)  NOT NULL,
                    `refresh_token` varchar(200)  NOT NULL,
                    `company_id` int(10) NOT NULL,
                    `expire_date` varchar(15)  NOT NULL,
                    `login_date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
                    PRIMARY KEY (`id`)
			) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci	AUTO_INCREMENT=1 ;
		");

        $this->db->query("
			CREATE TABLE IF NOT EXISTS `" . DB_PREFIX . "moloni_settings` (
                    `id` int(5) NOT NULL AUTO_INCREMENT,
                    `company_id` int(10),
                    `store_id` int(10),
                    `label` varchar(50)  NOT NULL,
                    `title` varchar(250)  NOT NULL,
                    `description` varchar(255)  ,
                    `value` varchar(250) ,
                    PRIMARY KEY (`id`)
			) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci	AUTO_INCREMENT=1 ;
		");

        $this->db->query("
			CREATE TABLE IF NOT EXISTS `" . DB_PREFIX . "moloni_documents` (
                    `id` int(50) NOT NULL AUTO_INCREMENT,
                    `company_id` int(10),
                    `store_id` int(10),
                    `order_id` int(10),
                    `order_total` varchar(25) ,
                    `invoice_id` int(25),
                    `invoice_total` varchar(25) ,
                    `invoice_type` varchar(50) ,
                    `invoice_date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
                    `invoice_status` int(10),
                    `metadata` TEXT ,
                    PRIMARY KEY (`id`)
			) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci	AUTO_INCREMENT=1 ;
		");

        $column_check = "SELECT column_name FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA='" . DB_DATABASE . "' AND column_name LIKE 'moloni_reference' LIMIT 1";
        $query = $this->db->query($column_check);
        $result = $query->row;
        if (empty($result)) {
            $this->db->query("ALTER TABLE `" . DB_PREFIX . "product_option_value` ADD `moloni_reference` VARCHAR( 255 ) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL;");
        }
    }

    public function dropTables()
    {
        $this->db->query("DROP TABLE `" . DB_PREFIX . "moloni`");
        $this->db->query("DROP TABLE `" . DB_PREFIX . "moloni_settings`");
    }
}

?>

