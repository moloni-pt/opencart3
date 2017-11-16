<?php
/* Moloni -
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

class ModelExtensionModuleMoloniMoloni extends Model
{

    public $libClass = array();
    private $libraries = array(
        "connection" => "connection.class.php",
        "errors" => "errors.class.php"
    );

    public function __construct()
    {

    }

    public function __set($key, $value)
    {

        $this->{$key} = $value;
    }

    public function lib($key)
    {
        return (isset($this->libClass[$key]) ? $this->libClass[$key] : null);
    }

    public function __call($name, $arguments)
    {
        return (isset($this->libClass[$name]) ? $this->libClass[$name] : null);
    }

    public function __get($name)
    {
        return (isset($this->libClass[$name]) ? $this->libClass[$name] : null);
    }

    public function loadLibrary()
    {
        foreach ($this->libraries as $name => $library) {
            try {
                require_once("library/" . $library);
                $class = 'moloni\\' . $name;
                $this->libClass[$name] = new $class($this);
            } catch (Exception $e) {
                echo 'Caught exception: ', $e->getMessage(), "\n";
            }
        }



        $this->lib('connection')->access_token = "1a0c8b3449be466a03fa3329c5b2875b66fcc8ea";
        $this->lib('connection')->refresh_token = "d45d98d93fd5b319efd36d5dedefc18283f670f4";
        $this->lib('connection')->expire_date = strtotime("-80 minutes");

        $status = $this->lib('connection')->start();
        if ($status) {
            $this->logged = true;
            echo "yey";
        } else {
            print_r($this->lib('errors')->getError());
        }
    }

    public function teste()
    {
        echo "teste";
    }
}
