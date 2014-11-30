<?php
namespace selective\ORM\Tests\MySQL;

use selective\ORM\Database;

trait TestHelper
{
    /**
     * @return Database
     */
    protected function getDB()
    {
        $db = new Database(
            $GLOBALS['test_dbname'],
            $this->getDriverClassName(),
            $this->getDriverParameters()
        );

        $db->getDriver()->executeUpdate('DROP TABLE IF EXISTS Books');
        $db->getDriver()->executeUpdate('DROP TABLE IF EXISTS Authors');

        $db->getDriver()->executeUpdate(<<<SQL
CREATE TABLE IF NOT EXISTS Authors (
  idAuthor INTEGER PRIMARY KEY NOT NULL AUTO_INCREMENT,
  name TEXT
)
SQL
        );

        $db->getDriver()->executeUpdate(<<<SQL
CREATE TABLE Books (
    idBook INTEGER PRIMARY KEY NOT NULL AUTO_INCREMENT,
    title TEXT NOT NULL,
    idAuthor INTEGER NOT NULL,
    isbn TEXT NOT NULL,
    description TEXT,
    dateCreated TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY(idAuthor) REFERENCES Authors(idAuthor)
)
SQL
        );

        $db->getDriver()->executeUpdate(<<<SQL
CREATE TABLE IF NOT EXISTS testprefix_Test (
    test int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8
SQL
        );

        $db->getDriver()->executeUpdate("DELETE FROM Books");
        $db->getDriver()->executeUpdate("DELETE FROM Authors");

        $db->getDriver()->executeUpdate("INSERT INTO Authors (idAuthor, name) VALUES (1, 'Author 1')");
        $db->getDriver()->executeUpdate("INSERT INTO Authors (idAuthor, name) VALUES (2, 'Author 2')");

        $db->getDriver()->executeUpdate("INSERT INTO Books (idBook, title, idAuthor, isbn, description) VALUES (1, 'My First Book', 1, '12345-6789', 'It wasn''t very good')");
        $db->getDriver()->executeUpdate("INSERT INTO Books (idBook, title, idAuthor, isbn, description) VALUES (2, 'My Second Book', 1, '12345-6790', 'It wasn''t very good either')");
        $db->getDriver()->executeUpdate("INSERT INTO Books (idBook, title, idAuthor, isbn, description) VALUES (3, 'My First Book', 2, '12345-6790', 'It was OK')");

        return $db;
    }

    protected function setUp()
    {
        if (empty($GLOBALS['mysql_enabled'])) {
            $this->markTestSkipped('mysql_* is not configured in phpunit.xml');
        }
    }

    protected function getDriverClassName()
    {
        return 'MySQL';
    }

    protected function getDriverParameters()
    {
        return array(
            'host' => $GLOBALS['mysql_hostname'],
            'port' => $GLOBALS['mysql_port'],
            'username' => $GLOBALS['mysql_username'],
            'password' => $GLOBALS['mysql_password']
        );
    }
}