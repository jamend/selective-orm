<?php
namespace selective\ORM\Tests\SQLite;

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

        $db->getDriver()->executeUpdate(<<<SQL
CREATE TABLE IF NOT EXISTS Authors (
  idAuthor INTEGER PRIMARY KEY NOT NULL,
  name TEXT
)
SQL
        );

        $db->getDriver()->executeUpdate(<<<SQL
CREATE TABLE IF NOT EXISTS Books (
    idBook INTEGER PRIMARY KEY NOT NULL,
    title TEXT NOT NULL,
    idAuthor INTEGER NOT NULL,
    isbn TEXT NOT NULL,
    description TEXT,
    FOREIGN KEY(idAuthor) REFERENCES Authors(idAuthor)
)
SQL
        );

        $db->getDriver()->executeUpdate("INSERT INTO Authors (idAuthor, name) VALUES (1, 'Author 1')");
        $db->getDriver()->executeUpdate("INSERT INTO Authors (idAuthor, name) VALUES (2, 'Author 2')");

        $db->getDriver()->executeUpdate("INSERT INTO Books (idBook, title, idAuthor, isbn, description) VALUES (1, 'My First Book', 1, '12345-6789', 'It wasn''t very good')");
        $db->getDriver()->executeUpdate("INSERT INTO Books (idBook, title, idAuthor, isbn, description) VALUES (2, 'My Second Book', 2, '12345-6790', 'It wasn''t very good either')");

        return $db;
    }


    protected function setUp()
    {
        if (empty($GLOBALS['sqlite_enabled'])) {
            $this->markTestSkipped('sqlite_* is not configured in phpunit.xml');
        }
    }

    protected function getDriverClassName()
    {
        return 'SQLite';
    }

    protected function getDriverParameters()
    {
        return array(
            'file' => $GLOBALS['sqlite_file']
        );
    }
}