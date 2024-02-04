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

$my_model = MyModel::findByPk(1);
```