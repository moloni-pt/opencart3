<?php
/* Moloni -
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

class ModelExtensionModuleMoloniOcdb extends Model
{

    public function qGetMoloniTokens()
    {
        $query = $this->db->query("SELECT DISTINCT * FROM `" . DB_PREFIX . "moloni` LIMIT 1");

        return $query->row;
    }
}
