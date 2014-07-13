<?php
namespace selective\ORM\Tests;

class BufferedRecordSetTest extends \PHPUnit_Framework_TestCase
{
    use TestHelper;

    public function testCount()
    {
        $db = $this->getDB();
        $recordSet = $db->{'Books'};

        $reportedCount = $recordSet->count();
        $count = 0;
        foreach ($recordSet as $id => $record) {
            $this->assertInstanceOf('selective\ORM\Record', $record);
            $count++;
        }

        $this->assertEquals($count, $reportedCount);
        $this->assertEquals($count, 2);
    }

    public function testIterate()
    {
        $db = $this->getDB();
        $recordSet = $db->{'Books'};

        $i = 0;
        foreach ($recordSet as $id => $record) {
            $this->assertInstanceOf('selective\ORM\Record', $record);
            switch ($i) {
                case 0:
                    $this->assertEquals($id, 1);
                    break;
                case 1:
                    $this->assertEquals($id, 2);
                    break;
            }
            $i++;
        }

        $this->assertEquals($i, 2);
    }

    public function testRewind()
    {
        $db = $this->getDB();
        $recordSet = $db->{'Books'};

        $firstCount = 0;
        foreach ($recordSet as $id => $record) {
            $this->assertInstanceOf('selective\ORM\Record', $record);
            $firstCount++;
        }

        $secondCount = 0;
        foreach ($recordSet as $id => $record) {
            $this->assertInstanceOf('selective\ORM\Record', $record);
            $secondCount++;
        }

        $this->assertEquals($firstCount, $secondCount);
    }

    public function testFirst()
    {
        $db = $this->getDB();
        $recordSet = $db->{'Books'};

        $first = $recordSet->first();

        $this->assertTrue($first !== false);
        $this->assertEquals($first->getId(), 1);
    }

    public function testLast()
    {
        $db = $this->getDB();
        $recordSet = $db->{'Books'};

        $last = $recordSet->last();

        $this->assertTrue($last !== false);
        $this->assertEquals($last->getId(), 2);
    }

    public function testArrayOffsetGet()
    {
        $db = $this->getDB();
        $table = $db->{'Books'};
        $this->assertTrue(isset($table[1]));
        $this->assertInstanceOf('selective\ORM\Record', $table[1]);
        $this->assertFalse(isset($table[3]));
    }

    public function testArrayOffsetSet()
    {
        $db = $this->getDB();
        $table = $db->{'Books'};
        $record = $table->create();
        $table[3] = $record;
        $this->assertTrue(isset($table[3]));
        $this->assertInstanceOf('selective\ORM\Record', $table[3]);
    }

    public function testToArray()
    {
        $db = $this->getDB();
        $table = $db->{'Books'};
        $rows = $table->toArray();
        $this->assertTrue(is_array($rows));
        $this->assertTrue(is_array(current($rows)));
    }
}