<?php

declare(strict_types=1);

namespace JuanchoSL\Orm\engine;

class DbCredentials implements \JsonSerializable
{

    protected $host;
    protected $port;
    protected $username;
    protected $password;
    protected $database;

    public function __construct($host, $username, $password, $dataBase, int $port = null)
    {
        $this->host = $host;
        $this->port = $port;
        $this->username = $username;
        $this->password = $password;
        $this->database = $dataBase;
    }

    public function getHost()
    {
        return $this->host;
    }

    public function getUsername()
    {
        return $this->username;
    }

    public function getPassword()
    {
        return $this->password;
    }

    public function getDataBase()
    {
        return $this->database;
    }

    public function getPort()
    {
        return $this->port;
    }

    public function jsonSerialize(): mixed
    {
        return [
            'host' => $this->host,
            'port' => $this->port,
            'username' => $this->username,
            'password' => $this->password,
            'database' => $this->database
        ];
    }
}
