<?php
/* Moloni
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */
namespace moloni;

class connection
{

    public $moloni_api_url = 'https://api.moloni.pt/v1/';
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
            if (time() > $this->moloni->expire_date) {
                if ($this->moloni->refresh_token) {
                    if (time() > strtotime('+5 days', $this->moloni->expire_date)) {
                        $this->moloni->errors->throwError('Refresh token expirou', 'A refresh token já expirou ' . date('Y-m-d H:i:s', strtotime('+6 days', $this->moloni->expire_date)), 'refresh_token');
                        $return = false;
                    } else {
                        $this->refreshHandler();
                        $return = true;
                    }
                } else {
                    $this->moloni->errors->throwError('Refresh token e falta', 'O refresh token não está definido', 'refresh_token');
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
        $url = 'grant/?grant_type=password'
            . '&client_id=' . $this->moloni->client_id
            . '&client_secret=' . $this->moloni->client_secret
            . '&username=' . $this->moloni->username
            . '&password=' . urlencode(html_entity_decode($this->moloni->password));
        
        $login = $this->curl($url, null);
        
        if ($login && isset($login['access_token']) && isset($login['refresh_token'])) {
            $this->moloni->updated_tokens = true;
            $this->moloni->access_token = $login['access_token'];
            $this->moloni->refresh_token = $login['refresh_token'];
            $this->moloni->expire_date = strtotime('+50 minutes');
            return true;
        }

        $this->moloni->errors->throwError('Dados incorrectos', "Os dados de login que inseriu não correspondem a uma conta Moloni. <a href='https://moloni.pt/' target='_BLANK'>Registar</a>", 'login');
        return false;
    }

    public function refreshHandler()
    {
        $url = 'grant/?grant_type=refresh_token'
            . '&client_id=' . $this->moloni->client_id
            . '&client_secret=' . $this->moloni->client_secret
            . '&refresh_token=' . $this->moloni->refresh_token;

        $refresh = $this->curl($url, null);
        if (isset($refresh['access_token'], $refresh['refresh_token']) && $refresh) {
            $this->moloni->updated_tokens = true;
            $this->moloni->access_token = $refresh['access_token'];
            $this->moloni->refresh_token = $refresh['refresh_token'];
            $this->moloni->expire_date = strtotime('+50 minutes');
            return true;
        }

        $this->moloni->errors->throwError('Dados incorrectos', "Os dados de login que inseriu não correspondem a uma conta Moloni. <a href='https://moloni.pt/' target='_BLANK'>Registar</a>", 'login');
        return false;
    }

    public function curl($action, $values = false, $debug = false)
    {
        $con = curl_init();
        $url = $this->moloni_api_url . $action . ($this->moloni->logged ? '/?access_token=' . $this->moloni->access_token : '');
        curl_setopt($con, CURLOPT_URL, $url);
        curl_setopt($con, CURLOPT_POST, true);
        curl_setopt($con, CURLOPT_POSTFIELDS, $values ? http_build_query($values) : false);
        curl_setopt($con, CURLOPT_HEADER, false);
        curl_setopt($con, CURLOPT_RETURNTRANSFER, true);

        $result_json = curl_exec($con);
        curl_close($con);

        $result_array = json_decode($result_json, true);

        $this->moloni->debug->addLog($url, $values, $result_array);

        if (isset($result_array['error_description']) && strtolower($result_array['error_description']) === 'invalid access token.') {
            $this->moloni->errors->throwError('Access token errada', 'Access token inválida', 'login');
            $this->moloni->logged = false;
            $this->moloni->updated_tokens = true;
            $this->moloni->access_token = '';
            $this->moloni->refresh_token = '';
            $this->moloni->expire_date = '';
            return false;
        }

        if (isset($result_array['error']) && strtolower($result_array['error']) === 'forbidden') {
            $result_array = array('Não tem permissões para aceder a este método ou a token está expirada');
        }

        return $result_array;
    }

    public function getMoloniImage($image)
    {
        if (!file_exists('../image/catalog/moloni')) {
            if (!mkdir($concurrentDirectory = '../image/catalog/moloni', 0777, true) && !is_dir($concurrentDirectory)) {
                throw new \RuntimeException(sprintf('Pasta "%s" não foi criada', $concurrentDirectory));
            }
        }

        $imageName = explode('/', $image, 3);
        if (!file_exists('../image/catalog/moloni/' . $imageName[2])) {
            $ch = curl_init('https://www.moloni.pt/_imagens/?macro=&img=' . $image);
            $fp = fopen('../image/catalog/moloni/' . $imageName[2], 'wb');
            curl_setopt($ch, CURLOPT_FILE, $fp);
            curl_setopt($ch, CURLOPT_HEADER, 0);
            curl_exec($ch);
            curl_close($ch);
            fclose($fp);
        }

        return 'catalog/moloni/' . $imageName[2];
    }
}
