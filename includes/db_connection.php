<?php
require_once 'config.php';

class Database {
    private $connection;
    
    public function __construct() {
        $this->connection = new mysqli(DB_HOST, DB_USERNAME, DB_PASSWORD, DB_NAME);
        
        if ($this->connection->connect_error) {
            die("Connection failed: " . $this->connection->connect_error);
        }
        
        $this->connection->set_charset("utf8");
    }
    
    public function getConnection() {
        return $this->connection;
    }
    
    public function prepare($query) {
        return $this->connection->prepare($query);
    }
    
    public function query($query) {
        return $this->connection->query($query);
    }
    
    public function escape_string($string) {
        return $this->connection->real_escape_string($string);
    }
    
    public function close() {
        $this->connection->close();
    }
    
    public function insert_id() {
        return $this->connection->insert_id;
    }
}

// Global database instance
$db = new Database();
?>
