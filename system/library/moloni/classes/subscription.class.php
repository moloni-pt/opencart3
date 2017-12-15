<?php
/* Moloni
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */
namespace moloni;

class subscription
{

    public function __construct(\moloni $moloni)
    {
        $this->moloni = $moloni;
    }

    public function count()
    {
        echo "test";
        $this->moloni->connection->testing();
    }
}
