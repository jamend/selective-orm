Selective Object-Relational Mapper
=============

Selective ORM is a database abstraction layer that simplifies working with databases. It is fully object-oriented, and infers the database schema (tables/columns/relationships) automatically, so you don't have to repeat it in your code.

* Build Status: [![Build Status](https://travis-ci.org/jamend/selective-orm.svg?branch=master)](https://travis-ci.org/jamend/selective-orm)
* Coverage Status: [![Coverage Status](https://coveralls.io/repos/jamend/selective-orm/badge.png?1)](https://coveralls.io/r/jamend/selective-orm)

Installing
==========

If you'd like to try Selective pending a stable release, you can add this to your composer.json:

```javascript
{
    "require": {
        "jamend/selective-orm": "dev-master"
    }
}
```

Usage
=====

Connecting to a database
```php
// first argument is the database name
// second argument is the driver implementation class name
// third argument is the parameter array for the driver
$db = new \selective\ORM\Database(
	'sample',
	'MySQL', // driver class
	['host' => 'localhost', 'username' => '...', 'password' => '...'] // MySQL driver parameters
);
```

Getting a record from the database
```php
$books = $db->Books; // get a Table instance for the Books table in the database

$book = $books->{12}; // get a Record instance for the book with ID 12

echo $book->title; // columns map directly to properties of the record
```

Looping through records with a where and order by clause
```php
$someBooks = $db->Books
	->where('tite LIKE ?', 'The%')
	->orderBy('datePublished', 'DESC')
; // fluent interface

// $books will lazy-load the records once you start iterating through them
foreach ($someBooks as $id => $book) {
	echo "#{$id} => {$book->title} <br />";
}
```

Create book
```php
$newBook = $books->create();
$newBook->title = 'A New Book';
$newBook->datePublished = time();
$newBook->save();

// $newBook's id is automatically set to the auto-increment ID
echo "New book created with ID {$newBook->getID()}";
```

Update book
```php
$book = $books->{13};
$book->title = 'A Better Title';
$book->save();
```

Delete book
```php
$books->{14}->delete();
```

Relationships
=============

Selective can also simplify the use of foreign key constraints that are defined in the schema. Here are some examples:

Get all books by an author
```php
$authors = $db->Authors;

$author = $authors->{1};
$books = $author->Books; // $books will be the Books table filtered by the author
```

Get the author of a book
```php
$book = $books->{15};
$author = $book->idAuthor; // $author will be a Record for the author matching the book's idAuthor
echo $author->name;
```

Set the author of a book
```php
$author = $authors->{2}
$book = $books->{16};

$book->idAuthor = $author; // '2' would also work
$book->save();
```

**Relationship optimization**

Related records are by default lazy loaded, meaning that the author for $book->idAuthor or the book record set for $author->Books will not be loaded until you request them. This is undesirable when working with a record set and its related records in batches, as it will result in many queries to the database being called in a loop. To demonstrate:

```php
foreach ($db->Books as $book) {
	// to get every book's author's name, a query must be sent to the database to fetch the book's author
	echo $book->idAuthor->name;
}
```

To avoid this, the RecordSet::with($tableName) method can be used to tell Selective to pre-load the related records for a RecordSet:

```php
foreach ($db->Books->with('Authors') as $book) {
	// the author for each book will already be pre-loaded using the same query that fetched the books
	echo $book->idAuthor->name;
}
```
