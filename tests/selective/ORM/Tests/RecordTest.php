<?php
namespace selective\ORM\Tests;

class RecordTest extends \PHPUnit_Framework_TestCase
{
    use TestHelper;

    public function testExists()
    {
        $db = $this->getDB();
        $recordSet = $db->{'Books'};

        $id = 1;
        $record = $recordSet->{$id};
        $this->assertTrue($record->exists());

        $record->delete();
        $this->assertFalse($record->exists());

        $record = $recordSet->create();
        $this->assertFalse($record->exists());
    }

    public function testToArray()
    {
        $db = $this->getDB();
        $recordSet = $db->{'Books'};
        $id = 1;
        $record = $recordSet->{$id};
        $asArray = $record->toArray();
        $this->assertTrue(is_array($asArray));
        $this->assertEquals($asArray['idAuthor'], '1');
    }

    public function testGetId()
    {
        $db = $this->getDB();
        $recordSet = $db->{'Books'};
        $id = 1;
        $record = $recordSet->{$id};
        $this->assertEquals($record->getId(), $id);
    }

    public function testProperty()
    {
        $db = $this->getDB();
        $table = $db->{'Books'};
        $book = $table->{1};
        $this->assertNotEmpty($book->title);
    }

    /**
     * @expectedException \PHPUnit_Framework_Error
     */
    public function testUndefinedProperty()
    {
        $db = $this->getDB();
        $table = $db->{'Books'};
        $book = $table->{1};
        $book->iDontExist;
    }

    /**
     * @expectedException \PHPUnit_Framework_Error
     */
    public function testUndefinedRawProperty()
    {
        $db = $this->getDB();
        $table = $db->{'Books'};
        $book = $table->{1};
        $book->getRawPropertyValue('iDontExist');
    }

    public function testInsert()
    {
        $db = $this->getDB();
        $recordSet = $db->{'Books'};

        $record = $recordSet->create();
        $record->title = 'Third time\'s the charm';
        $record->idAuthor = 1;
        $record->isbn = '12345-6791';

        $this->assertTrue($record->save());
        $this->assertNotNull($record->getId());
        $this->assertEquals(4, $record->getId());
    }

    public function testUpdate()
    {
        $db = $this->getDB();
        $recordSet = $db->{'Books'};

        $id = 1;
        $record = $recordSet->{$id};
        $record->title = 'A New Title';
        $this->assertTrue($record->save());

        $this->assertNotNull($record->getId());
    }

    public function testDelete()
    {
        $db = $this->getDB();
        $recordSet = $db->{'Books'};

        $id = 1;
        $record = $recordSet->{$id};
        $this->assertTrue($record->delete());
    }

    public function testExisted()
    {
        $db = $this->getDB();
        $recordSet = $db->{'Books'};

        $id = 1;
        $record = $recordSet->{$id};
        $this->assertTrue($record->existed());

        $record->delete();
        $this->assertTrue($record->existed());

        $record = $recordSet->create();
        $record->title = 'Third time\'s the charm';
        $record->idAuthor = 1;
        $record->isbn = '12345-6791';
        $record->save();
        $this->assertFalse($record->existed());
    }

    public function testDatetime()
    {
        $db = $this->getDB();

        $record = $db->Books->create();
        $record->title = 'A New Book';
        $record->idAuthor = 1;
        $record->isbn = '12345-6789';
        $record->save();

        $record = $db->Books->{1};
        // dateCreated is a timestamp column, so it should be set to the current time in UTC
        $this->assertTrue(abs(time() - $record->dateCreated) < 5);
    }

    public function testOneToMany()
    {
        $db = $this->getDB();
        $recordSet = $db->{'Authors'};
        $idAuthor = 1;
        $author = $recordSet->{$idAuthor};

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
        $this->assertEquals($count, 2);
    }

    public function testManyToOne()
    {
        $db = $this->getDB();
        $recordSet = $db->{'Books'};
        $id = 1;
        $book = $recordSet->{$id};

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
}