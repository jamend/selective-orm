<?php
namespace selective\ORM\Tests;

abstract class DriverTest extends \PHPUnit_Framework_TestCase
{
    private $driver;

    /**
     * @return \selective\ORM\Database
     */
    public abstract function getDb();

    public function testQuote()
    {
        $driver = $this->getDb()->getDriver();
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
        $driver = $this->getDb()->getDriver();
        $database = $this->getDb();
        $table = $driver->getTable($database, 'Books');

        $this->assertInstanceOf('selective\ORM\Table', $table);

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

    public function testProfiling()
    {
        $db = $this->getDb();
        $driver = $db->getDriver();
        $driver->setProfiling(true);
        $db->Books->count();
        $profilingData = $driver->getProfilingData();
        $this->assertGreaterThan(1, count($profilingData));
        $totals = $profilingData['total'];
        $this->assertGreaterThan(0, $totals['time']);
    }

    /**
     * @expectedException \Exception
     */
    public function testException()
    {
        $db = $this->getDb();
        $db->Books->where('how do i sql')->count();
    }
}