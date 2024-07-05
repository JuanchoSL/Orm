# ORM

## Description

Little methods collection in order to create SQL queries

## How to use

### Create credential for connection

```php
use JuanchoSL\Orm\engine\DbCredentials;

$credentials = new DbCredentials(getenv('DB_HOST'), getenv('DB_USER'), getenv('DB_PASS'), getenv('DB_DATABASE'));
```

### Create connection

```php
use JuanchoSL\Orm\engine\Drivers\Mysqli;

$resource = new Mysqli($credentials);
```

### Use a logger

You can inject a logger in order to save queries and errors from drivers

```php
use JuanchoSL\Logger\Logger;

$logger = new Logger(FULLPATH);
$resource->setLogger($logger);
```

### Create a query using query builder

```php
use JuanchoSL\Orm\Querybuilder\QueryBuilder;

$builder = QueryBuilder::getInstance()->select()->from('table_name')->where(['campo', 'valor'], ['dato', 2]);//SELECT * FROM table_name WHERE (campo='valor' AND dato=2)
$builder = QueryBuilder::getInstance()->select()->from('table_name')->where(['campo', 'valor'])->where(['dato', 2]);//SELECT * FROM table_name WHERE (campo='valor') AND (dato=2)
$builder = QueryBuilder::getInstance()->select()->from('table_name')->where(['campo', 'valor'])->where(['dato', 2, '>']);//SELECT * FROM table_name WHERE (campo='valor') AND (dato > 2)
$builder = QueryBuilder::getInstance()->select()->from('table_name')->where(['campo', 'valor'])->where(['dato', [2], true]);//SELECT * FROM table_name WHERE (campo='valor') AND (dato IN (2))
$builder = QueryBuilder::getInstance()->select()->from('table_name')->where(['campo', 'valor'])->where(['dato', [2], false]);//SELECT * FROM table_name WHERE (campo='valor') AND (dato NOT IN (2))
$builder = QueryBuilder::getInstance()->select()->from('table_name')->where(['campo', 'valor'])->where(['dato', null, true]);//SELECT * FROM table_name WHERE (campo='valor') AND (dato IS NULL))
$builder = QueryBuilder::getInstance()->select()->from('table_name')->where(['campo', 'valor'])->where(['dato', null, false]);//SELECT * FROM table_name WHERE (campo='valor') AND (dato IS NOT NULL))
$cursor = $resource->execute($builder);

$num_results = $cursor->count();
$results = $cursor->get();
$cursor->free();
```

### For use with datamodels, set the connection previously created

```php
Model::setConnection($resource);

//for retrieve an entity using primary key
$my_model = MyModel::findByPk(1);

//for search a collection of elements
$my_collection = MyModel::where(['status', '1', '='])->get();
```

### Morphying data

In order to cast or morph a data value, you can create a getter-setter functions with the following structure into the class model.
For a field named **bith_date**, you can create a protected function (in order to avoid direct access) with the name **BirthDate**, and preppend the **get** or **set** key word.

- The get method receive the database value and convert to the desired value and then return it.
- The set method receive the passed value and then convert it to the table type and return it converted

#### Get example

```php
protected function getBirthDate(int $timestamp)
{
    return (new DateTime())->setTimestamp($timestamp);
}
```

#### Set examples

You can create some variations in order to accept few options

Direct conversions

```php
protected function setBirthDate(DateTimeInterface $datetime)
{
    return $datetime->getTimestamp();
}
```

Dynamic conversions

```php
protected function setBirthDate(DateTimeInterface|int $datetime)
{
    if($datetime instanceof DateTimeInterface){
        $datetime = $datetime->getTimestamp();
    }
    return $datetime;
}
```

Validations

```php
protected function setEmail(string $email)
{
    if(!filter_var($email, FILTER_VALIDATE_EMAIL)){
        throw new Exception("The email: {$email} does not have a valid format");
    }
    return $email;
}
```

### Relations

Using relations, you can directly link to parent or childs using a simple method. You have 4 methods availables

- OneToOne (The second point to the first)
- OneToMany (Childs collection that are pointing from the seconds to the first)
- BelongsToOne (The first point to the second)
- BelongsToMany (The first point to a pivot table than point to the seconds)

#### Examples
- remote_table_field_name by default is created as {remote_primary_key_name}
- this_table_fiel_name by default is created as {remote_table_name}_{remote_primary_key_name}
```php
public function parent()
{
    return $this->BelongsToOne(ParentModel::getInstance(), 'remote_table_field_name', 'this_table_field_name');
}
```

- remote_table_field_name by default is created as {local_table_name}_{local_primary_key_name}
- this_table_fiel_name by default is created as {local_primary_key_name}
```php
public function childs()
{
    return $this->OneToMany(ChildModel::getInstance(), 'remote_table_field_name', 'this_table_field_name');
}
```

#### Using
When you call a relation, using method, return a querybuilder in order to apply filters to remote objects.
XXXToMany relations, return a collection, XXXToOne return an instance of ModelInterface
```php
$collection = MyModel::findByPk(1)->childs()->where(['field','value','='])->limit(5)->get();
```
If you call as parameter, the get method is called directly without filters
```php
$collection = MyModel::findByPk(1)->childs;
```


### For use cache with datamodels, use CacheModel instead Model and set the connection previously created

```php
CachedModel::setConnection($resource);
CachedModel::setCache(SimpleCacheAdapter::getInstance(new SessionCache('cache_database')));

//for retrieve an entity using primary key
$my_model = MyModel::findByPk(1);

//for search a collection of elements
$my_collection = MyModel::where(['status', '1', '='])->get();
```
