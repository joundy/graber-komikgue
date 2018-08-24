<?php

class db
{
	private $dbDriver = "mysql";
	private $host = "127.0.0.1";
	private $username = "root";
	private $password = "";
	private $database = "";
    protected $connection;
    
	public function __construct(){
		try{
            $this->connection = new PDO($this->dbDriver.':host='.$this->host.';dbname='.$this->database,$this->username,$this->password);
            $this->connection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
		}
		catch (PDOException $e){
        	die("Koneksi error: " . $e->getMessage());
    	}
	}
}