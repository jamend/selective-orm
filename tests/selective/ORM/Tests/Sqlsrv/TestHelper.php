<?php
namespace selective\ORM\Tests\Sqlsrv;

use selective\ORM\Database;

trait TestHelper
{
    /**
     * @return Database
     */
    protected function getDB()
    {
        $parameters = $this->getDriverParameters();
        $pdo = new \PDO("sqlsrv:Server={$parameters['host']}", $parameters['username'], $parameters['password']);
        $stmt = $pdo->prepare("IF NOT EXISTS(SELECT * FROM sys.databases WHERE name = ?) CREATE DATABASE [{$GLOBALS['test_dbname']}]");
        $stmt->bindParam(1, $GLOBALS['test_dbname']);
        $stmt->execute();
        $pdo = null;

        $db = new Database(
            $GLOBALS['test_dbname'],
            $this->getDriverClassName(),
            $parameters
        );

        $db->getDriver()->executeUpdate("IF EXISTS (SELECT * FROM sys.objects WHERE name = 'Books') DROP TABLE Books");
        $db->getDriver()->executeUpdate("IF EXISTS (SELECT * FROM sys.objects WHERE name = 'Authors') DROP TABLE Authors");
        $db->getDriver()->executeUpdate("IF EXISTS (SELECT * FROM sys.objects WHERE name = 'testprefix_Test') DROP TABLE testprefix_Test");

        $db->getDriver()->executeUpdate(<<<SQL
CREATE TABLE Authors (
  idAuthor INTEGER PRIMARY KEY NOT NULL IDENTITY(1,1),
  name TEXT
)
SQL
        );

        $db->getDriver()->executeUpdate(<<<SQL
CREATE TABLE Books (
    idBook INTEGER PRIMARY KEY NOT NULL IDENTITY(1,1),
    title TEXT NOT NULL,
    idAuthor INTEGER NOT NULL,
    isbn TEXT NOT NULL,
    description TEXT,
    dateCreated TIMESTAMP,
    FOREIGN KEY(idAuthor) REFERENCES Authors(idAuthor)
)
SQL
        );

        $db->getDriver()->executeUpdate(<<<SQL
CREATE TABLE testprefix_Test (
    test int DEFAULT NULL
)
SQL
        );

        $db->getDriver()->executeUpdate("DELETE FROM Books");
        $db->getDriver()->executeUpdate("DELETE FROM Authors");

        $db->getDriver()->executeUpdate("INSERT INTO Authors (name) VALUES ('Author 1')");
        $db->getDriver()->executeUpdate("INSERT INTO Authors (name) VALUES ('Author 2')");

        $db->getDriver()->executeUpdate("INSERT INTO Books (title, idAuthor, isbn, description) VALUES ('My First Book', 1, '12345-6789', 'It wasn''t very good')");
        $db->getDriver()->executeUpdate("INSERT INTO Books (title, idAuthor, isbn, description) VALUES ('My Second Book', 1, '12345-6790', 'It wasn''t very good either')");
        $db->getDriver()->executeUpdate("INSERT INTO Books (title, idAuthor, isbn, description) VALUES ('My First Book', 2, '12345-6790', 'It was OK')");

        return $db;
    }

    protected function setUp()
    {
        if (empty($GLOBALS['sqlsrv_enabled'])) {
            $this->markTestSkipped('sqlsrv_* is not configured in phpunit.xml');
        }
    }

    protected function getDriverClassName()
    {
        return 'Sqlsrv';
    }

    protected function getDriverParameters()
    {
        return array(
            'host' => $GLOBALS['sqlsrv_host'],
            'username' => $GLOBALS['sqlsrv_username'],
            'password' => $GLOBALS['sqlsrv_password'],
            'schema' => $GLOBALS['sqlsrv_schema']
        );
    }
}