#PHPLib

###MYSQL
-

**init**

```php
include 'mysql.php'; 
use CAI_mysql;
$manager = CAI_mysql\Manager::create("localhost","$database","$Account","$password");
$name = "DB_4";
```
**Database create and delete**

```php
$manager->createDatabase($name);
$manager->deleteDatabase($name);
```

**Table create and delete**

```php
$manager->createTable("caihongji",array(
	"name nvarchar(50) not null",
	"id int not null primary key",
	"age int not null",
	"sex char(5) not null"
));
$manager->deleteTable("caihongji");
```

**Row insert and delete**

```php
$manager->insertRow("stuff",
	array("name","owner"),
	array("iPad Pro","2")
);
$manager->insertRows(array(
	array(
		"stuff", array("name","owner"), array("iPad Pro",2)
	),
	array(
		"stuff", array("name","owner"), array("iPad Air","4")
	),
	array(
		"stuff", array("name","owner"), array("iPhone 4","2")
	),
	array(
		"stuff", array("name","owner"), array("iPhone 5s","4")
	),
	array(
		"stuff", array("name","owner"), array("iPhone 6 Plus","4")
	),
	array(
		"stuff", array("name","owner"), array("iPhone 8","4")
	)
));
$manager->deleteRow("stuff",
	"id = $i"
);
```

**Pre handle insert**

```php
$preHandle = $manager->createPreHnadleInsertionSQL("stuff",array(
	"name","owner"
));
$preHandle->execute(array(
	"iPad mini","2"
));
$preHandle->execute(array(
	"iPad Pro","4"
));
```

**select and update**

```php
$result = $manager->select("stuff",
	"id,owner",
	"id = 2"
);
$manager->update("stuff","id = 75", array(
	"name" => "Samsung Galaxy",
	"owner" => "2"
));
```
