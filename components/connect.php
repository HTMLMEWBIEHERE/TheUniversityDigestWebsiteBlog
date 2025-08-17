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
            try {
                $this->connection = new PDO("mysql:host=$this->host;dbname=$this->db_name", $this->user_name, $this->user_password);
                $this->connection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            } catch(PDOException $e) {
                echo "Connection failed: " . $e->getMessage();
                die(); 
            }
        }

        return $this->connection;
    }
}

$conn = new PDO("mysql:host=localhost;dbname=udg_db", "root", "");
?>