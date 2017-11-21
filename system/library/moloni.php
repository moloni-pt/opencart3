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
    private $libs = array(
        "entities" => "moloni/entities.class.php"
    );
    private $dependencies = array(
        "connection" => "connection.class.php",
        "errors" => "errors.class.php",
    );

    public function __construct()
    {
        $this->loadDependencies();
        return true;
    }

    public function __get($name)
    {
        echo $name;
        if (!isset($this->{$name})) {
            require($this->libs[$name]);
            $class = 'moloni\\' . $name;
            $this->{$name} = new $class($this);
        }
        return $this->{$name}($args);
    }

    public function __call($name, $args)
    {
        echo __CLASS__;
        if (method_exists($this, $name)) {
            return $this->{$name}($args);
        } else {
            require($this->libs[$name]);
        }
    }

    public function loadDependencies()
    {
        foreach ($this->dependencies as $name => $library) {
            try {
                require_once("moloni/" . $library);
                $class = 'moloni\\' . $name;
                $this->{$name} = new $class($this);
            } catch (Exception $e) {
                echo 'Caught exception: ', $e->getMessage(), "\n";
            }
        }

        $this->entities();
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
