<?php
namespace jamend\Selective\Tests;

class SQLiteTest extends DriverTest
{
    protected function setUp()
    {
        if (empty($GLOBALS['sqlite_enabled'])) {
            $this->markTestSkipped('sqlite_* is not configured in phpunit.xml');
        }

        $this->getDriver()->executeUpdate(<<<SQL
CREATE TABLE IF NOT EXISTS Authors (
  idAuthor INTEGER PRIMARY KEY NOT NULL,
  name TEXT
)
SQL
);
        $this->getDriver()->executeUpdate(<<<SQL
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
    }

    protected function getDriverClassName()
    {
        return 'PDO\SQLite';
    }

    protected function getDriverParameters()
    {
        return array(
           'file' => $GLOBALS['sqlite_file']
        );
    }
}