<?php

declare(strict_types=1);

/**
 * Class Name:  DatabaseService
 * Purpose:     Provide one shared PDO connection to the OLMS database.
 * Description: Every service receives its connection from here, so the
 *              connection settings live in a single place (src/config).
 */
class DatabaseService
{
    private static ?PDO $connection = null;

    /**
     * Function/Method Name: getConnection
     * Purpose:      Return the shared database connection, creating it on first use.
     * Parameters:   none
     * Return Value: PDO - connection with exceptions enabled and associative fetch mode
     * Description:  Builds the DSN for MySQL (default) or SQLite from the config file.
     */
    public static function getConnection(): PDO
    {
        if (self::$connection === null) {
            self::$connection = self::createConnection(require __DIR__ . '/../config/config.php');
        }

        return self::$connection;
    }

    /**
     * Function/Method Name: createConnection
     * Purpose:      Open a new PDO connection from a config array.
     * Parameters:   array $appConfig - values returned by src/config/config.php
     * Return Value: PDO - a configured connection
     * Description:  Kept separate from getConnection() so tests can open their own connection.
     */
    public static function createConnection(array $appConfig): PDO
    {
        if ($appConfig['db_driver'] === 'sqlite') {
            $connection = new PDO('sqlite:' . $appConfig['db_sqlite_path']);
            $connection->exec('PRAGMA foreign_keys = ON');
        } else {
            $dataSourceName = sprintf(
                'mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4',
                $appConfig['db_host'],
                $appConfig['db_port'],
                $appConfig['db_name']
            );
            $connection = new PDO($dataSourceName, $appConfig['db_user'], $appConfig['db_password']);
        }

        $connection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $connection->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

        return $connection;
    }
}
