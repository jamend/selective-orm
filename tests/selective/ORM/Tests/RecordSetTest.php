<?php
namespace selective\ORM\Tests;

class RecordSetTest extends \PHPUnit_Framework_TestCase
{
    use TestHelper;

    public function testSimpleGetters()
    {
        $db = $this->getDB();
        $recordSet = $db->{'Books'}->openRecordSet();
        $this->assertInstanceOf('selective\ORM\Table', $recordSet->getTable());
        $this->assertInstanceOf('selective\ORM\Driver', $recordSet->getDriver());
        $this->assertInstanceOf('selective\ORM\Query', $recordSet->getQuery());
    }

    public function testGetRecordSet()
    {
        $db = $this->getDB();
        $recordSet = $db->{'Books'};
        $this->assertNotNull($recordSet);
        $this->assertInstanceOf('selective\ORM\RecordSet\Buffered', $recordSet);
    }

    public function testOpenRecordSet()
    {
        $db = $this->getDB();
        $recordSet = $db->{'Books'};

        $recordSet2 = $recordSet->openRecordSet();
        $this->assertNotNull($recordSet2);
        $this->assertInstanceOf('selective\ORM\RecordSet\Buffered', $recordSet2);
        $this->assertTrue($recordSet !== $recordSet2);

        $recordSet3 = $recordSet2->openRecordSet();
        $this->assertNotNull($recordSet3);
        $this->assertInstanceOf('selective\ORM\RecordSet\Buffered', $recordSet3);
        $this->assertTrue($recordSet2 !== $recordSet3);
    }

    public function testWhere()
    {
        $db = $this->getDB();
        $recordSet = $db->{'Books'}->where("title LIKE ?", '%First%');
        $this->assertCount(1, $recordSet);
        foreach ($recordSet as $record) {
            $this->assertContains('First', $record->title);
        }
    }

    public function testOrderBy()
    {
        $db = $this->getDB();

        $recordSet = $db->{'Books'}->orderBy('idBook', 'ASC');
        $this->assertCount(2, $recordSet);
        $i = 0;
        foreach ($recordSet as $record) {
            switch ($i) {
                case 0:
                    $this->assertEquals($record->getId(), 1);
                    break;
                case 1:
                    $this->assertEquals($record->getId(), 2);
                    break;
            }
            $i++;
        }

        $recordSet = $db->{'Books'}->orderBy('idBook', 'DESC');
        $this->assertCount(2, $recordSet);
        $i = 0;
        foreach ($recordSet as $record) {
            switch ($i) {
                case 0:
                    $this->assertEquals($record->getId(), 2);
                    break;
                case 1:
                    $this->assertEquals($record->getId(), 1);
                    break;
            }
            $i++;
        }
    }

    public function testLimit()
    {
        $db = $this->getDB();

        $recordSet = $db->{'Books'}->limit(1);
        $this->assertCount(1, $recordSet);
        $i = 0;
        foreach ($recordSet as $record) {
            switch ($i) {
                case 0:
                    $this->assertEquals($record->getId(), 1);
                    break;
            }
            $i++;
        }

        $recordSet = $db->{'Books'}->limit(1, 1);
        $this->assertCount(1, $recordSet);
        $i = 0;
        foreach ($recordSet as $record) {
            switch ($i) {
                case 0:
                    $this->assertEquals($record->getId(), 2);
                    break;
            }
            $i++;
        }
    }

    public function testRawSql()
    {
        $db = $this->getDB();
        $recordSet = $db->{'Books'}->sql('SELECT title FROM Books');

        $record = $recordSet->first(1);
        $this->assertNotNull($record);
        $this->assertInstanceOf('selective\ORM\Record', $record);
        $this->assertEquals($record->title, 'My First Book');

        // as array
        $recordSet = $db->{'Books'};
        $recordSet = $recordSet->sql('SELECT title FROM Books');
        $rows = $recordSet->toArray();
        $this->assertCount(2, $rows);
        $this->assertCount(1, $rows[0]);
        $this->assertNotNull($rows[0]['title']);
        $this->assertEquals($rows[0]['title'], 'My First Book');
        $this->assertEquals($rows[1]['title'], 'My Second Book');
    }

    public function testWithOneToMany()
    {
        $db = $this->getDB();
        $table = $db->{'Authors'}->with('Books');
        $idAuthor = 1;
        /** @var \selective\ORM\Record $author */
        $author = $table->{$idAuthor};

        $this->assertTrue($author->hasRelatedTable('Books'));
        $this->assertFalse($author->hasRelatedTable('Authors'));
        $this->assertFalse($author->hasRelatedTable('IDontExist'));
        $this->assertFalse(isset($author->{'IDontExist'}));

        // explicit method
        $books = $author->getRelatedTable('Books');
        $this->assertInstanceOf('selective\ORM\RecordSet', $books);
        $this->assertFalse($author->getRelatedTable('Authors'));
        $this->assertFalse($author->getRelatedTable('IDontExist'));

        // magic getter
        $count = 0;
        foreach ($author->Books as $idBook => $book) {
            /** @var \selective\ORM\Record $book */
            $count++;
            $this->assertInstanceOf('selective\ORM\Record', $book);
            $this->assertEquals($book->getRawPropertyValue('idAuthor'), $author->getId());
        }
        $this->assertEquals($count, 1);
    }

    public function testWithManyToOne()
    {
        $db = $this->getDB();
        $table = $db->{'Books'}->with('Authors');
        $id = 1;
        /** @var \selective\ORM\Record $book */
        $book = $table->{$id};

        $this->assertTrue($book->hasForeignRecord('idAuthor'));
        $this->assertFalse($book->hasForeignRecord('idBook'));
        $this->assertFalse($book->hasForeignRecord('idIDontExist'));
        $this->assertFalse(isset($book->{'idIDontExist'}));

        // explicit method
        $author = $book->getForeignRecord('idAuthor');
        $this->assertInstanceOf('selective\ORM\Record', $author);
        $this->assertFalse($book->getForeignRecord('idBook'));
        $this->assertFalse($book->getForeignRecord('idIDontExist'));

        // magic getter
        $author = $book->idAuthor;
        $idAuthor = $book->getRawPropertyValue('idAuthor');
        $this->assertInstanceOf('selective\ORM\Record', $author);
        $this->assertEquals($author->getTable()->getName(), 'Authors');
        $this->assertEquals($author->getId(), $idAuthor);
    }

    public function testArrayWith()
    {
        $db = $this->getDB();
        $recordSet = $db->{'Authors'}->with('Books');

        $rows = $recordSet->toArray();
        $this->assertInternalType('array', $rows);
        $this->assertInternalType('array', $rows[1]);
        $this->assertNotNull($rows[1]['name']);
        $this->assertNotNull($rows[1]['Books']);
        $this->assertInternalType('array', $rows[1]['Books']);
        $this->assertNotNull($rows[1]['Books'][1]);
        $this->assertNotNull($rows[1]['Books'][1]['title']);
    }
}