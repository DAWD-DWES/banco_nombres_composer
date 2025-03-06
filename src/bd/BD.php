<?php

namespace App\BD;

use Dotenv\Dotenv;
use \PDO;

class BD {

    protected static $bd = null;

    private function __construct(string $host, string $database, string $username, string $password) {
        try {
            self::$bd = new PDO("mysql:host=" . $host . ";dbname=" . $database, $username, $password);
            self::$bd->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch (PDOException $e) {
            throw new Exception($e->getMessage(), $e->getCode());
        }
    }

    public static function getConexion() {
        if (!self::$bd) {
            $dotenv = Dotenv::createImmutable(__DIR__ . "/../../");
            $dotenv->load();
            $host = $_ENV['DB_HOST'];
            $database = $_ENV['DB_DATABASE'];
            $usuario = $_ENV['DB_USUARIO'];
            $password = $_ENV['DB_PASSWORD'];
            new BD($host, $database, $usuario, $password);
        }
        return self::$bd;
    }
}
