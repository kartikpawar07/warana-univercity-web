<?php
declare(strict_types=1);

function getDbConnection(): mysqli
{
    static $connection = null;

    if ($connection instanceof mysqli) {
        return $connection;
    }

    $host = 'localhost';
    $dbname = 'warana_db';
    $username = 'root';
    $password = '';

    $connection = @new mysqli($host, $username, $password, $dbname);

    if ($connection->connect_error) {
        if ($connection->connect_errno === 1049) {
            $tempConnection = new mysqli($host, $username, $password);
            if ($tempConnection->connect_error) {
                die('Database bootstrap failed: ' . $tempConnection->connect_error);
            }

            $tempConnection->query("CREATE DATABASE IF NOT EXISTS `$dbname` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
            $tempConnection->close();

            $connection = new mysqli($host, $username, $password, $dbname);
        }

        if ($connection->connect_error) {
            die('Connection failed: ' . $connection->connect_error);
        }
    }

    $connection->set_charset('utf8mb4');

    return $connection;
}

$conn = getDbConnection();
?>
