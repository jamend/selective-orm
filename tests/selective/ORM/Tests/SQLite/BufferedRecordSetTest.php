<?php
namespace selective\ORM\Tests\SQLite;

class BufferedRecordSetTest extends \selective\ORM\Tests\BufferedRecordSetTest
{
    use TestHelper;

    public function testDirtyTracking()
    {
        $db = $this->getDB();

        $recordSet1 = $db->{'Books'};
        $oldCount = $recordSet1->count();

        $recordSet2 = $db->{'Books'};
        $recordSet2->count(); // force load

        $id = 1;
        $record = $recordSet2->{$id};
        $record->delete();

        $newCount = $recordSet1->count();
        $this->assertEquals($newCount, $recordSet2->count());
        $this->assertNotEquals($oldCount, $newCount);
    }
}