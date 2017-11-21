<?php
/* Moloni
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */
namespace moloni;

class customers
{

    public function __construct(\moloni $moloni)
    {
        $this->moloni = $moloni;
        echo "Cheguei";
    }

    public function count()
    {
        echo "test";
        $this->moloni->connection->testing();
    }
}
