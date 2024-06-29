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
use JuanchoSL\Orm\querybuilder\QueryBuilder;

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

### For use cache with datamodels, use CacheModel instead Model and set the connection previously created
```php
CacheModel::setConnection($resource);
CachedModel::setCache(SimpleCacheAdapter::getInstance(new SessionCache('cache_database')));

//for retrieve an entity using primary key
$my_model = MyModel::findByPk(1);

//for search a collection of elements
$my_collection = MyModel::where(['status', '1', '='])->get();
```