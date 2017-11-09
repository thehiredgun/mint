# mint
A crisp PDO-abstraction layer class

### Installation:
Recommended installation is via [Composer](https://getcomposer.org).

```composer require thehiredgun/mint```

or

```
"require": {
    ...,
    "thehiredgun/mint": "^1.0",
    ...
}
```

### Quick-Start:
*mint* is here to provide a crisp, clean interface between application and database layers.
It uses the PDO object you hand it, gathers some meta data for your database, then makes your
life easier by giving you methods to do just about anything you need to do, with one line of PHP.

```
use TheHiredGun\Mint\Mint;
...

// Mint::__construct takes a PDO object as it's argument
$db = new Mint(new PDO($dsn, $username, $password));
```
These first four methods are used when you write a query manually
```
// Mint::select, selectOne, update, and delete all take:
// a query as its first argument
// and optionally an associative array of 'column_name' => $columnValue parameters to match *your query*

// Mint::select returns an array of records
$books = $db->select('SELECT * FROM books WHERE author = :author ORDER BY title ASC', ['author' => 'Vladimir Nabokov']);

// Mint::selectOne returns the first returned record
$bookId = $db->selectOne('SELECT * FROM books WHERE author = :author AND title = :title', [
    'title'  => 'Pale Fire',
    'author' => 'Vladimir Nabokov',
]);

// Mint::update returns $stmt->rowCount();
$rowCount = $db->update('UPDATE books SET author = :author WHERE title in("Pale Fire", "Lolita")', [
    'author' => 'Vladimir Nabokov',
]);

// Mint::delete returns $stmt->rowCount()
$rowCount = $db->delete('DELETE FROM books WHERE author = :author', ['author' => 'Vladimir Nabokov']);
```

### Shorthand Methods
There are a few shorthand methods which makes a lot of database-related work go much more quickly:

```
// Mint::selectOneById
// takes a Table Name and the record's Primary Key as arguments
// returns the record from that table with that primary key
$db->selectOneById('books', $bookId); // returns the row from books where books' primary key = $bookId

// Mint::deleteOneById
// same arguments as selectOneById
// returns $stmt->rowCount()
$db->deleteOneById('books', $bookId);

// Mint::insertOne
// first argument is the name of the table to which you want to insert a record
// second argument is an associative array of 'column_name' => $columnValue parameters to match *the table*
// returns the primary key for the record you just
$bookId = $db->insertOne('books', ['author' => 'Vladimir Nabokov', 'title' => 'Pale Fire']);

// Mint::updateOne
// first argument is the name of the table to which you want to update a record
// second argument is an associative array of 'column_name' => $columnValue parameters to match *the table*
// third argument is the primary key of the recordy you want to update
// returns $stmt->rowCount()
$db->updateOne('books', ['author' => 'Vladimir Nabokov'], $bookId);
```

### Hints & Best Practices
- Generally speaking you want to configure your PDO error mode to 'Exception: `$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION)`
- *mint* binds *named parameters*, so ...
- If you are writing a query manually , you'll want to write the tokens with a ':' preceding them, and the indexes of the parameters you supply should match those tokens (Mint will automatically make sure each parameter-token has a ':')
- If you are using a shorthand method like insertOne or updateOne, Mint will automatically match the your parameters with the columns of the table, and throw out any which do not match. No need to write a query, match parameters, or add ':' to your indexes. Mint will also *only* add columns and values to the query if they are included in you array of parameters.

