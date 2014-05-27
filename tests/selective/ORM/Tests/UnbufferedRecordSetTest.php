<?php
namespace selective\ORM\Tests;

class UnbufferedRecordSetTest extends \PHPUnit_Framework_TestCase
{
    use TestHelper;

    public function testIterate()
    {
        $db = $this->getDB();
        $recordSet = $db->{'Books'}->unbuffered();

        $count = 0;
        foreach ($recordSet as $id => $record) {
            $this->assertInstanceOf('selective\ORM\Record', $record);
            $count++;
        }

        $this->assertEquals($count, 2);
    }

    public function testRewind()
    {
        $db = $this->getDB();
        $recordSet = $db->{'Books'}->unbuffered();

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
}