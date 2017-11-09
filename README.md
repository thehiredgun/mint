# mint
A crisp PDO-abstraction layer class

## Installation:
Recommended installation is via [Composer](https://getcomposer.org):

```composer require thehiredgun/mint```

or in your composer.json:

```
"require": {
    ...,
    "thehiredgun/mint": "^1.0",
    ...
}
```

## Quick-Start:
*mint* is here to provide a crisp, clean interface between application and database layers.
It uses the PDO object you hand it, gathers some meta data for your database, then makes your
life easier by giving you methods to do just about anything you need to do, with one line of PHP.

```
use TheHiredGun\Mint\Mint;
...

// Mint::__construct takes a PDO object as it's argument
$db = new Mint(new PDO($dsn, $username, $password));
```
### These first four methods are used when you write a query manually
Each is written to return the type of response you would typically want to receive when executing that type of query:
- select returns an array of rows
- selectOne returns the first returned row
- update returns the $PDOStatement->rowCount(), which is the number of rows affected by your query
- delete also returns the $PDOStatement->rowCount()
Each takes the query as it's first argument:
```'UPDATE my_table SET token_one = :token_one, token_two = :token_two WHERE token_three = :token_three```
And (optionally) as it's second argument, an associative array of parameters:
```
[
    'token_one'   => $token_one_value,
    'token_two'   => $token_two_value,
    'token_three' => $token_three_value,
]
```

Mint *always* tokenizes your parameters (i.e. adds ':' to the index for each parameter), so though
your *query* needs to be written with those tokens, your *array of parameters does not need to be*.

```
$books = $db->select('SELECT * FROM books WHERE author = :author ORDER BY title ASC', [
    'author' => 'Vladimir Nabokov'
]);

$bookId = $db->selectOne('SELECT * FROM books WHERE author = :author AND title = :title', [
    'title'  => 'Pale Fire',
    'author' => 'Vladimir Nabokov',
]);

$numAffectedRows = $db->update('UPDATE books SET author = :author WHERE title IN("Pale Fire", "Lolita")', [
    'author' => 'Vladimir Nabokov',
]);

$numAffectedRows = $db->delete('DELETE FROM books WHERE author = :author', [
    'author' => 'Vladimir Nabokov'
]);
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
// second argument is an associative array of 'column_name' => $columnValue parameters to match the table
// returns the primary key for the record you just
$bookId = $db->insertOne('books', ['author' => 'Vladimir Nabokov', 'title' => 'Pale Fire']);

// Mint::updateOne
// first argument is the name of the table to which you want to update a record
// second argument is an associative array of 'column_name' => $columnValue parameters to match the table
// third argument is the primary key of the recordy you want to update
// returns $stmt->rowCount()
$db->updateOne('books', ['author' => 'Vladimir Nabokov'], $bookId);
```

### Hints, Best Practices, & Limitations
- At present, *mint* works with MySQL and SQLite3. It can be extended to allow for the use of other PDO Drivers.
- Generally speaking you want to configure your PDO error mode to 'Exception: `$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION)`
- Mint binds *named parameters*, using the format ':' + column_name, so...:
- If you are writing a query manually , you'll want to write the tokens with a ':' preceding them, and the indexes of the parameters you supply should match those tokens (Mint will automatically make sure each parameter-token has a ':')
- If you are using a shorthand method like insertOne or updateOne, Mint will automatically match the your parameters with the columns of the table, and throw out any which do not match. No need to write a query, match parameters, or add ':' to your indexes. Mint will also *only* add columns and values to the query if they are included in you array of parameters.

