selective-orm
=============

Selective is (for now) a database abstraction layer that simplifies working with databases. It is fully object-oriented, and infers the database schema (tables/columns/relationships) automatically, so you don't have to repeat it in your code. Eventually it will also include ORM features, so you can connect a schema to your application's model.

**Examples**

Connecting to a database
```php
// first argument is the DB implementation class name
// second argument is the parameter array for the DB implementation; each item will call a corresponding setter
$db = \jamend\Selective\DB::loadDB(
	'PDOMySQL', // implementation class
	['dbname' => 'sample', 'host' => 'localhost', 'username' => '...', 'password' => '...'] // PDOMySQL parameters
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

**Relationships**

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
$author = $book->related('idAuthor'); // $author will be a Record for the author matching the book's idAuthor
```
