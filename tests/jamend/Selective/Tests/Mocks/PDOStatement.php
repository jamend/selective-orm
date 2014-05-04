<?php
namespace jamend\Selective\Tests\Mocks;

class PDOStatement extends \PDOStatement
{
    private $sql;
    private $fakeData = array(
        'SHOW TABLES FROM `test` LIKE ?' => array(
            0 =>
            array (
                'Tables_in_sample' => 'Authors',
            ),
            1 =>
            array (
                'Tables_in_sample' => 'Books',
            ),
        ),
        'SHOW CREATE TABLE `Books`' => array(
            0 => array (
                'Table' => 'Books',
                'Create Table' => 'CREATE TABLE `Books` (
  `idBook` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(128) NOT NULL,
  `idAuthor` int(11) NOT NULL,
  `isbn` varchar(32) NOT NULL,
  `description` text,
  PRIMARY KEY (`idBook`),
  KEY `idAuthor` (`idAuthor`),
  CONSTRAINT `books_ibfk_1` FOREIGN KEY (`idAuthor`) REFERENCES `authors` (`idAuthor`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8'
            ),
        ),
        'SELECT `test`.`Books`.`idBook`, `test`.`Books`.`title`, `test`.`Books`.`idAuthor`, `test`.`Books`.`isbn`, `test`.`Books`.`description` FROM `test`.`Books` WHERE (`idBook` = ?)' => array(
            0 =>
            array (
                0 => '1',
                1 => 'My First Book',
                2 => '1',
                3 => '12345-6789',
                4 => 'It wasn\'t very good',
            ),
        )
    );

    private $affectedRows = 0;

    public function __construct($sql)
    {
        $this->sql = $sql;
    }

    public function execute($bound_input_params = null)
    {
        switch (strtolower(substr($this->sql, 5))) {
            case 'insert':
            case 'update':
            case 'delete':
                $this->affectedRows = 1;
                break;
            case 'select':
                $this->affectedRows = 0;
                break;
        }
        return true;
    }

    public function rowCount()
    {
        return $this->affectedRows;
    }

    public function fetch($how = null, $orientation = null, $offset = null)
    {
        $row = current($this->fakeData[$this->sql]);
        next($this->fakeData[$this->sql]);
        return $row;
    }
}