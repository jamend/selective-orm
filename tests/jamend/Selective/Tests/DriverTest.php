<?php
namespace jamend\Selective\Tests;

abstract class DriverTest extends TestCase
{
    private $database;
    private $driver;

    /**
     * @return string
     */
    abstract protected function getDriverClassName();

    /**
     * @return array
     */
    abstract protected function getDriverParameters();

    /**
     * @return \jamend\Selective\Database
     */
    protected function getDatabase()
    {
        if (!isset($this->database)) {
            $this->database = new \jamend\Selective\Database(
                $GLOBALS['test_dbname'],
                $this->getDriverClassName(),
                $this->getDriverParameters()
            );
        }
        return $this->database;
    }

    /**
     * @return \jamend\Selective\Driver
     */
    protected function getDriver()
    {
        if (!isset($this->driver)) {
            $this->driver = $this->getDatabase()->getDriver();
        }
        return $this->driver;
    }

    public function testQuote()
    {
        $driver = $this->getDriver();
        $this->assertSame($driver->quote(null), 'null');
        $this->assertSame($driver->quote(true), '1');
        $this->assertSame($driver->quote(false), '0');
        $this->assertSame($driver->quote(0), '"0"');
        $this->assertSame($driver->quote(1000000), '"1000000"');
        $this->assertSame($driver->quote(123456.789), '"123456.789"');
        $this->assertSame($driver->quote(''), '""');
        $this->assertSame($driver->quote('test'), '"test"');
        $this->assertSame($driver->quote('t\'est'), '"t\\\'est"');
    }

    public function testGetTable()
    {
        $driver = $this->getDriver();
        $database = $this->getDatabase();
        $table = $driver->getTable($database, 'Books');

        $this->assertInstanceOf('jamend\Selective\Table', $table);

        $columns = $table->getColumns();

        $this->assertArrayHasKey('idBook', $columns);
        $this->assertArrayHasKey('title', $columns);
        $this->assertArrayHasKey('idAuthor', $columns);
        $this->assertArrayHasKey('isbn', $columns);
        $this->assertArrayHasKey('description', $columns);

        $this->assertTrue($columns['idBook']->isPrimaryKey());
        $this->assertTrue($columns['idBook']->isAutoIncrement());
        $this->assertFalse($columns['title']->isPrimaryKey());
        $this->assertTrue($columns['description']->isAllowNull());
        $this->assertFalse($columns['title']->isAllowNull());

        $foreignKeys = $table->getForeignKeys();

        $this->assertArrayNotHasKey('title', $foreignKeys);
        $this->assertArrayHasKey('idAuthor', $foreignKeys);

        $constraints = $table->getConstraints();
        $this->assertCount(1, $constraints);
        $this->assertEquals(
            array(
                'localColumns' => array('idAuthor'),
                'relatedTable' => 'Authors',
                'relatedColumns' => array('idAuthor')
            ),
            current($constraints)
        );
    }
}