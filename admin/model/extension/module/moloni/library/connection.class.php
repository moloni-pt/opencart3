<?php
/* Moloni
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */
namespace moloni;

class connection
{

    public $moloni_api_url = "https://api.moloni.com/v1/";
    public $messages;
    private $access_token = false;
    private $refresh_token = false;
    private $expire_date = false;

    function __construct(\ModelExtensionModuleMoloniMoloni $moloni)
    {
        $this->moloni = $moloni;
        return false;
    }

    public function __set($name, $value)
    {
        $this->{$name} = $value;
    }

    public function start()
    {
        return $this->checkValidateToken();
    }

    public function setInfo($access_token = false, $expire_date = false, $refresh_token = false)
    {
        $this->access_token = $access_token;
        $this->expire_date = $expire_date;
        $this->refresh_token = $refresh_token;
    }

    private function checkValidateToken()
    {
        $return = false;

        if (!$this->access_token) {
            $this->messages["title"] = "Access Token não definido";
            $return = false;
        } else {
            if (strtotime("-10 minutes") > $this->expire_date) {
                if ($this->refresh_token) {
                    if (strtotime("-10 minutes") > ($this->expire_date + strtotime("6 days"))) {
                        $this->messages["title"] = "Refresh Token expirado";
                        $return = false;
                    } else {
                        echo "Vamos fazer refresh";
                        $return = true;
                    }
                } else {
                    $this->messages["title"] = "Access Token expirado";
                    $return = false;
                }
            } else {
                $return = true;
            }
        }

        return $return;
    }

    private function tokenRefresh()
    {

    }

    public function curl($url)
    {
        $curl = curl_init();
        $url = $this->moloni_api_url . $url . "/?access_token=" . $this->access_token;
        $my_values = array('company_id' => 5, 'product_id' => 534521);

        curl_setopt($con, CURLOPT_URL, $url);
        curl_setopt($con, CURLOPT_POST, true);
        curl_setopt($con, CURLOPT_POSTFIELDS, http_build_query($my_values));
        curl_setopt($con, CURLOPT_HEADER, false);
        curl_setopt($con, CURLOPT_RETURNTRANSFER, true);

        $res_curl = curl_exec($con);
        curl_close($con);

// análise do resultado
        $res_txt = json_decode($res_curl, true);
        if (!isset($res_txt['error'])) {
            echo 'Sucesso: ' . print_r($res_txt, true) . '';
        } else {
            echo 'Houston, we\'ve got a Problem!';
            echo 'Erro: ' . print_r($res_txt, true) . '';
        }
    }
}
