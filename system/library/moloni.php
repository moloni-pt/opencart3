<?php
/* Moloni -
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

class moloni
{

    public $access_token;
    public $refresh_token;
    public $expire_date;
    public $company_id = false;
    public $logged = false;
    private $libraries = array(
        "connection" => "connection.class.php",
        "errors" => "errors.class.php"
    );

    public function __construct()
    {
        $this->loadLibraries();
        return true;
    }

    public function loadLibraries()
    {
        foreach ($this->libraries as $name => $library) {
            try {
                require_once("moloni/" . $library);
                $class = 'moloni\\' . $name;
                $this->{$name} = new $class($this);
            } catch (Exception $e) {
                echo 'Caught exception: ', $e->getMessage(), "\n";
            }
        }
    }

    public function verifyTokens()
    {
        if ($this->connection->start()) {
            $this->logged = true;
        }
    }

    public function teste()
    {
        echo "teste";
    }
}
