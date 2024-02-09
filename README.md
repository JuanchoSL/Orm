# ORM

## Description

Little methods collection in order to create SQL queries

## How to use

### Create credential for connection
```
use JuanchoSL\Orm\engine\DbCredentials;

$credentials = new DbCredentials(getenv('DB_HOST'), getenv('DB_USER'), getenv('DB_PASS'), getenv('DB_DATABASE'));
```

### Create connection
```
use JuanchoSL\Orm\engine\Drivers\Mysqli;

$resource = new Mysqli($credentials, Mysqli::RESPONSE_OBJECT);
```

### Use a logger
You can inject a logger in order to save queries and errors from drivers
```
use JuanchoSL\Logger\Logger;

$logger = new Logger(FULLPATH);
$resource->setLogger($logger);
```

### Create a query using query builder
```
use JuanchoSL\Orm\querybuilder\QueryBuilder;

$builder = QueryBuilder::getInstance()->select()->from($this->table)->where(['test', 'valor'])->where(['dato', 2]);
$cursor = $resource->execute($builder);

$num_results = $cursor->count();
$results = $cursor->get();
$cursor->free();
```

### For use with datamodels, set the connection previously created
```
DBConnection::setConnection($resource);

//for retrieve an entity using primary key
$my_model = MyModel::findByPk(1);

//for search a collection of elements
$my_collection = MyModel::where(['status', '1', '='])->get();
```