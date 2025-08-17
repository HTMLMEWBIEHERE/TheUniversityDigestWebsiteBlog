<?php

class Database
{
    private $host = 'localhost';      
    private $user_name = 'root';       
    private $user_password = '';          
    private $db_name = 'udg_db'; 

    protected $connection; 
    function connect()
    {
        if ($this->connection === null) {
            $this->connection = new PDO("mysql:host=$this->host;dbname=$this->db_name", $this->user_name, $this->user_password);
        }

        return $this->connection;
    }
}

