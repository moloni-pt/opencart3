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

    function __construct(\moloni $moloni)
    {
        $this->moloni = $moloni;
        return true;
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
        if (!$this->moloni->access_token) {
            $return = false;
        } else {
            if (strtotime("-10 minutes") > $this->moloni->expire_date) {
                if ($this->moloni->refresh_token) {
                    if (strtotime("+9 days") > (strtotime("1 days"))) {
                        $this->moloni->errors->throwError("Refresh token expirou", "A refresh token já expirou " . date('Y-m-d H:i:s', ($this->moloni->expire_date)), "refresh_token");
                        $return = false;
                    } else {
                        echo "Vamos fazer refresh";
                        $return = true;
                    }
                } else {
                    $this->moloni->errors->throwError("Refresh token e falta", "O refresh token não está definido", "refresh_token");
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

    public function curl($url, $values = false)
    {
        $curl = curl_init();
        $url = $this->moloni_api_url . $url . "/?access_token=" . $this->access_token;

        curl_setopt($con, CURLOPT_URL, $url);
        curl_setopt($con, CURLOPT_POST, true);
        curl_setopt($con, CURLOPT_POSTFIELDS, $values ? http_build_query($my_values) : false);
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
