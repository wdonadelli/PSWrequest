# PHP/SQLite Interaction System Via Web Request

The PHP/SQLite Interaction System Via Web Request - PSWrequest is a class written in [PHP](https://www.php.net/) intended to interact with the [SQLite](https://www.sqlite.org/) database.

This class inherits the structure of the default [SQLite3 library](https://www.php.net/manual/pt_BR/book.sqlite3.php), so the library must be enabled for this tool to work.

The class differential is to provide simplified mechanisms of communication (requests and responses) between the database and the requestor.

## Constructor

When creating the object, the database name must be entered as an argument, otherwise a database will be [defined in memory](https://www.sqlite.org/inmemorydb.html) (`:memory:`).

```php
$object = new PSWrequest("database");
```

## Interactions

During and after constructing the object, each triggered method promotes an interaction that records the occurrence of errors.

The class was built with the intention of silencing errors, avoiding harming the reply message to the requester.

To know if a certain action was satisfactory, methods were created that provide the status of the last interaction with the object.

These methods are not a source of interactions.

### `getError`

This method returns `True` if the last interaction did not occur satisfactorily, otherwise it returns `False`.

```php
$object->getError();
```

### `getType`

This method returns the type of last interaction. There are two possible values: "php" and "sql". When the last interaction does not involve any database transactions, the return will be "php", otherwise "sql".

```php
$object->getType();
```
### `getMessage`

This method returns the error message, if any, regarding the last interaction. If no error has occurred, it will return an empty string.

```php
$object->getMessage();
```

### `getData`

This method returns the content of the last interaction. There will be value returned when the interaction type is "sql" of type "SELECT" (search result) or "INSERT" (last row id entered). In other cases, returns `NULL`.

```php
$object->getData();
```
### `getResponse`

This method is intended to promote response to the requester. After calling this method, the script will be terminated and actions that were scheduled after the method was executed will not be executed.

If an object construction error occurs, this method will be executed and the script terminated.

 The method returns, in JSON format, three pieces of information:
 
- `error`: the same answer given by the `getError` method;
- `message`: the same answer given by the `getMessage` method, except when the error is of type "php", where it is restricted to report that there was an error communicating with the database; and
- `data`: the same answer given by the `getData` method;

```php
$object->getResponse();
```

### `getQuery`

It has the same mechanism as the `getResponse` method, however it only returns the contents of the `getData` method. Ideal for returning the query result (SELECT) or for knowing the last ID entered in the insert (INSERT).

```php
$object->getQuery();
```

## Methods

### `sql($input)`

This method performs an action in the format accepted by SQLite, whose operation must be entered as a method argument.

```php
$object->sql("INSERT INTO myTable (name, age, height) VALUES ('myName', '18', '1.70')");
```

### `insert($table, $data)`

This method is a shortcut to making SQL inserts directly from the data sent by the requester.

It has two required arguments, the table name (`$table`) and the array with the data to be inserted (`$data`).

The keys of the array must correspond to the columns of the table and their respective values ​​will be added to the database. See the example below:

```php
$myData = Array(
	"name"   => "myName",
	"age"    => 18,
	"height" => 1.7
);
$object->insert("myTable", $myData);
```

The above action would have the same result as the procedure below:

```php
$object->sql("INSERT INTO myTable (name, age, height) VALUES ('myName', '18', '1.70')");
```

If the data submitted in the request form already conforms to the table formatting, simply use PHP's `$ _POST` or `$ _GET` variables to insert the data into the table:

```php
$object->insert("myTable", $_POST);
```

If you need a more complex action, use the `sql` method.

### `update($table, $data, $where)`

This method is a shortcut and its operation is similar to the `insert` method, however it aims to update information in the database.

he method has one more argument than the `insert` method, the` $where` argument. This argument should indicate which key within the `$data` argument will be used to set the update target.

```php
$myData = Array(
	"id"     => 1,
	"name"   => "myName",
	"age"    => 18,
	"height" => 1.7
);
$object->update("myTable", $myData, "id");
```

The above action would have the same result as the procedure below:

```php
$object->sql("UPDATE myTable SET name = 'myName', age = '18', height = '1.70' WHERE id = '1'");
```

Only one key can be set and the comparison will use the `=` sign. If you need a more complex action, use the `sql` method.

### `delete($table, $data, $where)`

This method is a shortcut and its operation is similar to the `update` method, however it aims to delete information in the database.

```php
$myData = Array(
	"id"     => 1,
	"name"   => "myName",
	"age"    => 18,
	"height" => 1.7
);
$object->delete("myTable", $myData, "id");
```

The above action would have the same result as the procedure below:

```php
$object->sql("DELETE FROM myTable WHERE id = '1'");
```

If you need a more complex action, use the `sql` method.

### `view($table[, $data, $where])`

This method is a shortcut and is intended to make queries.

Unlike the `update` and `delete` methods, the `$data` and `$where` arguments are optional. However, if a value is set to `$where`, there must be an array entered in `$data` containing the entered key.

```php
$myData = Array(
	"id"     => 1,
	"name"   => "myName",
	"age"    => 18,
	"height" => 1.7
);
$object->view("myTable", $myData, "id");
```

The above action would have the same result as the procedure below:

```php
$object->sql("SELECT * FROM myTable WHERE id = '1'");
```

The search will return all columns from the table (`*`). If you need a more complex action, use the `sql` method or or create direct queries (VIEW) in the database.

## Version

- _v1.0.0 (2019-10-28)_

## Author

- Willian Donadelli ([wdonadelli@gmail.com](wdonadelli@gmail.com))
