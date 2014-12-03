<?php
namespace selective\ORM\Tests;

use selective\ORM\Database;

abstract class DatabaseTest extends \PHPUnit_Framework_TestCase
{
    use TestHelper;

    public function testPreset()
    {
        $prefixDb = new Database('test', '\selective\ORM\Tests\Mocks\Driver', ['prefix' => 'testprefix_']);
        $this->assertEquals(count($prefixDb->getTables()), 1);
        $this->assertFalse($prefixDb->hasTable('Books'));
        $this->assertTrue($prefixDb->hasTable('Test'));
        $table = $prefixDb->{'Test'};
        $this->assertInstanceOf('selective\ORM\Table', $table);
    }

    public function testTransactionRollback()
    {
        $db = $this->getDb();
        $recordSet = $db->{'Books'};

        $oldCount = $recordSet->count();

        $db->startTransaction();

        $record = $recordSet->create();
        $record->title = 'Test book';
        $record->idAuthor = 1;
        $record->isbn = '12345-6789';
        $record->save();

        $db->rollback();

        $newCount = $recordSet->count();

        $this->assertEquals($oldCount, $newCount);
    }

    public function testTransactionCommit()
    {
        $db = $this->getDb();
        $recordSet = $db->{'Books'};

        $oldCount = $recordSet->count();

        $db->startTransaction();

        $record = $recordSet->create();
        $record->title = 'Test book';
        $record->idAuthor = 1;
        $record->isbn = '12345-6789';
        $record->save();

        $db->commit();

        $newCount = $recordSet->count();

        $this->assertNotEquals($oldCount, $newCount);
    }

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