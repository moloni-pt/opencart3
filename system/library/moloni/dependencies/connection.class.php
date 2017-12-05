<?php
/* Moloni
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */
namespace moloni;

class connection
{

    public $moloni_api_url = "https://api.moloni.pt/v1/";
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
        if ($this->moloni->username && $this->moloni->password) {
            return $this->loginHandler();
        } elseif (!$this->moloni->access_token) {
            $return = false;
        } else {
            if (strtotime("-50 minutes") < $this->moloni->expire_date) {
                if ($this->moloni->refresh_token) {
                    if (strtotime("+9 days") < $this->moloni->expire_date) {
                        $this->moloni->errors->throwError("Refresh token expirou", "A refresh token já expirou " . date('Y-m-d H:i:s', ($this->moloni->expire_date)), "refresh_token");
                        $return = false;
                    } else {
                        echo "Vamos fazer refresh";
                        echo "Aqui se a referesh token não der, vamos mandar para o login, porque as tokens são inválidas";
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

    public function loginHandler()
    {
        $url = "grant/?grant_type=password"
            . "&client_id=" . $this->moloni->client_id
            . "&client_secret=" . $this->moloni->client_secret
            . "&username=" . $this->moloni->username
            . "&password=" . $this->moloni->password;
        $login = $this->curl($url);
        if ($login && isset($login["access_token"]) && isset($login["refresh_token"])) {
            $this->moloni->updated_tokens = true;
            $this->moloni->access_token = $login['access_token'];
            $this->moloni->refresh_token = $login['refresh_token'];
            $this->moloni->expire_date = strtotime("+50 minutes");
            return true;
        } else {
            $this->moloni->errors->throwError("Dados incorrectos", "Os dados de login que inseriu não correspondem a uma conta Moloni. <a href='https://moloni.pt/' target='_BLANK'>Registar</a>", "login");
            return false;
        }
    }

    public function tokenRefresh()
    {

    }

    public function curl($action, $values = false)
    {
        $con = curl_init();
        $url = $this->moloni_api_url . $action . ($this->moloni->logged ? "/?access_token=" . $this->moloni->access_token : "");
        curl_setopt($con, CURLOPT_URL, $url);
        curl_setopt($con, CURLOPT_POST, true);
        curl_setopt($con, CURLOPT_POSTFIELDS, $values ? http_build_query($values) : false);
        curl_setopt($con, CURLOPT_HEADER, false);
        curl_setopt($con, CURLOPT_RETURNTRANSFER, true);

        $result_json = curl_exec($con);
        curl_close($con);

        $result_array = json_decode($result_json, true);
        if (isset($result_array['error_description']) && $result_array['error_description'] == 'Invalid access token.') {
            $this->moloni->errors->throwError("Access token errada", "Access token inválida", "login");
            return false;
        }
        return $result_array;
    }
}
