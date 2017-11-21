<?php
/* Moloni -
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

class moloni
{

    public $namespace = "moloni\\";
    public $access_token;
    public $refresh_token;
    public $expire_date;
    public $company_id = false;
    public $logged = false;
    private $libs = array(
        "customers" => "customers.class.php",
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
        if (!isset($this->{$name})) {
            $this->load("moloni/classes/" . $this->libs[$name], $name, $this->namespace . $name);
        }
        return $this->{$name};
    }

    public function loadDependencies()
    {
        foreach ($this->dependencies as $name => $depend) {
            try {
                $this->load("moloni/dependencies/" . $depend, $name, $this->namespace . $name);
            } catch (Exception $e) {
                echo 'Caught exception: ', $e->getMessage(), "\n";
            }
        }

        $this->customers->count();
    }

    public function verifyTokens()
    {
        if ($this->connection->start()) {
            $this->logged = true;
        }
    }

    private function load($path, $name, $class_name)
    {
        require_once($path);
        $this->{$name} = new $class_name($this);
    }
}
