<?php
/* Moloni -
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

class ModelExtensionModuleMoloniMoloni extends Model
{

    public $logged = false;
    public $connection;
    public $erros;
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

    public function loadLibrary()
    {
        foreach ($this->libraries as $name => $library) {
            try {
                require_once("library/" . $library);
                $class = 'moloni\\' . $name;
                $this->{$name} = new $class($this);
            } catch (Exception $e) {
                echo 'Caught exception: ', $e->getMessage(), "\n";
            }
        }


        $this->connection->access_token = "1a0c8b3449be466a03fa3329c5b2875b66fcc8ea";
        $this->connection->refresh_token = "d45d98d93fd5b319efd36d5dedefc18283f670f4";
        $this->connection->expire_date = strtotime("-80 minutes");

        $status = $this->connection->start();
        if ($status) {
            $this->logged = true;
            echo "yey";
        } else {
            print_r($this->errors->getError());
        }
    }
}
