<?php
/* Moloni -
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

use moloni\connection;

/**
 * @property connection connection
 */
class moloni
{

    public $namespace = "moloni\\";
    public $updated_tokens = false;
    public $access_token = false;
    public $refresh_token = false;
    public $expire_date = false;
    public $username = false;
    public $password = false;
    public $client_secret = false;
    public $client_id = false;
    public $company_id = false;
    public $logged = false;
    /* private $libs = array(
      "customers" => "customers.class.php",
      "companies" => "companies.class.php",
      "document_sets" => "document_sets.class.php",
      ); */
    private $dependencies = array(
        'connection' => 'connection.class.php',
        'errors' => 'errors.class.php',
        'debug' => 'debug.class.php',
    );

    public function __construct()
    {
        $this->loadDependencies();
    }

    public function __get($name)
    {
        if (!isset($this->{$name}) && !isset($this->dependencies[$name])) {
            $this->load('moloni/classes/' . $name . '.class.php', $name, $this->namespace . $name);
        }

        return $this->{$name};
    }

    public function __set($name, $value)
    {
        $this->{$name} = $value;
    }

    public function __isset($name)
    {
        // TODO: Implement __isset() method.
    }

    public function __call($name, $documentType)
    {
        $this->documentType = empty($documentType[0]) ? 'documents' : $documentType[0];

        if (!isset($this->{$name}) && !isset($this->dependencies[$name])) {
            $this->load('moloni/classes/' . $name . '.class.php', $name, $this->namespace . $name);
        }

        return $this->{$name};
    }

    public function loadDependencies()
    {
        foreach ($this->dependencies as $name => $depend) {
            try {
                $this->load('moloni/dependencies/' . $depend, $name, $this->namespace . $name);
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

    /**
     * @param string $path
     * @param string $name
     * @param string $class_name
     */
    private function load($path, $name, $class_name)
    {
        $fullPath = DIR_SYSTEM . '/library/' . $path;

        try {
            require_once $fullPath;
            $this->{$name} = new $class_name($this);
        } catch(Exception $exception) {
            #throw new \RuntimeException('Error loading moloni dependency from ' . $fullPath);
        }
    }
}
