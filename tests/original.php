<?php

use remote\database\Database;
use remote\database\DatabaseAdapter;
use system\cache\CacheAdapter;
use PHPUnit\Framework\TestCase;
use remote\database\engine\Sqlite;
use remote\database\engine\DbCredentials;
use remote\database\datamodel\DBConnection;
class DBTest extends TestCase
{

    protected $db;
    private $loops = 3;

    public function setUp():void
    {
        try {
            $this->db = new TestDb();
        } catch (exceptions\NonLoadedModuleException $ex) {
            echo __CLASS__ . "[{$ex->getCode()}] " . $ex->getMessage();
            exit;
        }
    }

    public function testInsert()
    {
        for ($i = 1; $i <= $this->loops; $i++) {
            $id = $this->db->insert(array('test' => 'valor', 'dato' => $i));
            $this->assertTrue(!empty($id), "Recuperación del id de un insert");
        }
    }

    public function testConditions()
    {

        $condition = [
            'OR' => [
                'AND' => ['test' => 'valor', 'dato' => 1],
                'IN' => ['dato' => [3]]
            ]
        ];
//        $cursor = $this->db->select($condition);
//        $this->assertEquals(2, $this->db->getNResults(), "Check 0");

        $cursor = $this->db->select(array('test' => 'valor', 'dato' => 2));
        $this->assertEquals(1, $this->db->getNResults(), "Check 1");

        $cursor = $this->db->select(['AND' => ['test' => 'valor', 'dato' => 2]]);
        $this->assertEquals(1, $this->db->getNResults(), "Check 2");

        $cursor = $this->db->select(['OR' => ['test' => 'valor', 'dato' => 2]]);
        $this->assertEquals($this->loops, $this->db->getNResults(), "Check 3");

        $cursor = $this->db->select(['IN' => ['test' => ['valor', 'valore']]]);
        $this->assertEquals($this->loops, $this->db->getNResults(), "Check 4");

        $cursor = $this->db->select(array('test="valor"', 'dato=2'));
        $this->assertEquals(1, $this->db->getNResults(), "Check 5");

        $cursor = $this->db->select(array('test="valor"', 'test<>"otro"'));
        $this->assertEquals($this->loops, $this->db->getNResults(), "Check 6");

        $cursor = $this->db->select(['AND' => ['test="valor"', 'dato=2']]);
        $this->assertEquals(1, $this->db->getNResults(), "Check 7");

        $cursor = $this->db->select(['OR' => ['test="valor"', 'dato=2']]);
        $this->assertEquals($this->loops, $this->db->getNResults(), "Check 8");

        $cursor = $this->db->select(['OR' => ['test="valor"', 'test="valore"']]);
        $this->assertEquals($this->loops, $this->db->getNResults(), "Check 9");

        $cursor = $this->db->select(['NOT IN' => ['dato' => [2, 4]]]);
        $this->assertEquals($this->loops - 1, $this->db->getNResults(), "Check 10");
    }

    public function testSelect()
    {
        for ($i = 1; $i <= $this->loops; $i++) {
            $cursor = $this->db->select(array('test' => 'valor'), $this->db->keys()[0], 0, $i);
            $values = $this->db->result($cursor);
            $this->assertTrue(is_array($values));
            $this->assertEquals($i, count($values));
            $this->assertTrue(isset($values[$i - 1]));
            $this->assertTrue($values[$i - 1] instanceof stdClass);
            $id = (in_array($this->db->typeDB, [DatabaseAdapter::TYPE_MONGOCLIENT, DatabaseAdapter::TYPE_MONGO])) ? (string) $values[$i - 1]->_id : $values[$i - 1]->id;
            $this->assertTrue(!empty($id), "Recuperación del id de un select");
        }
    }

    public function testSelectPaginated()
    {
        $i = $this->loops - 1;
        $cursor = $this->db->select(array('test' => 'valor'), $this->db->keys()[0], 0, $i);
        $this->assertEquals($i, $this->db->getNResults());
        $values = $this->db->result($cursor);
        $this->assertTrue(is_array($values));
        $this->assertEquals($i, count($values));
        $this->assertEquals($this->loops, $this->db->getNResultsNoPagination());
        $this->assertTrue(isset($values[$i - 1]));
        $this->assertTrue($values[$i - 1] instanceof stdClass);
        $id = (in_array($this->db->typeDB, [DatabaseAdapter::TYPE_MONGOCLIENT, DatabaseAdapter::TYPE_MONGO])) ? (string) $values[$i - 1]->_id : $values[$i - 1]->id;
        $this->assertTrue(!empty($id), "Recuperación del id de un select");
    }

    public function testRestart()
    {
        $remover = TestDb::find(array());
        foreach ($remover as $remo) {
            $remo->remove();
        }
        $this->testTruncate();
    }

    public function testSaveInsert()
    {
        for ($i = 1; $i <= $this->loops; $i++) {
            $obj = new TestDb();
            $obj->test = 'valores';
            $obj->dato = $i;
            $id = $obj->save();
            $this->assertTrue(!empty($id), "Recuperación del id de un insert");
        }
    }

    public function testSaveUpdate()
    {
        $objs = TestDb::find();
        foreach ($objs as $obj) {
            $this->assertEquals('valores', $obj->test, "Comprobación del valor original");
            $obj->test = 'valor';
            $n = $obj->save();
            $this->assertEquals(1, $n, "Recuperación del nÃºmero de elementos modificados con update");
            $key = $obj->keys()[0];
            $id = $obj->$key;
            $obj2 = TestDb::findOne([$key => $id]);
            $this->assertEquals('valor', $obj2->test, "Comprobación del valor original");
        }
    }

    public function testSelectByPk()
    {
        $elements = TestDb::find();
        $this->assertTrue($elements->hasElements(), "Find return elements");
        foreach ($elements as $element) {
            $obj = TestDb::findByPk($element->getPrimaryKeyValue());
            $this->assertInstanceOf(TestDb::class, $obj);
            $this->assertTrue($obj instanceof TestDb);
//            $this->assertTrue($obj instanceof Database);
            $this->assertEquals($element, $obj);
        }
    }

    public function testSelectFindPaginated()
    {
        for ($i = 1; $i <= $this->loops; $i++) {
            $objs = TestDb::find(array('test' => 'valor'), $this->db->keys()[0], $i, 0);
            $this->assertTrue($objs instanceof \values\Collection);
            $this->assertTrue($objs->hasElements());
            $this->assertContainsOnlyInstancesOf(TestDb::class, $objs);
            $this->assertEquals($i, $objs->size());
            foreach ($objs as $obj) {
                $this->assertInstanceOf(TestDb::class, $obj);
//                $this->assertTrue($obj instanceof Database);
                $this->assertTrue($obj->loaded);
                if (!in_array($this->db->typeDB, [DatabaseAdapter::TYPE_MONGOCLIENT, DatabaseAdapter::TYPE_MONGO])) {
                    foreach ($obj->columns() as $column) {
                        $this->assertObjectHasAttribute($column, $obj); //PHPUnit 6
                    }
                }
            }
        }
    }

    public function testUpdate()
    {
        $modificateds = $this->db->update(array('test' => 'value'), array('test' => 'valor'));
        $this->assertEquals($this->loops, $modificateds, "Update elements");
    }

    public function testSerialize()
    {
        $var = TestDb::find();
        $this->assertTrue($var->hasElements(), "Collection have elements");
        $this->assertContainsOnlyInstancesOf(TestDb::class, $var, "Collection are a few of Test class");
        $var->rewind();
        $current = $var->current();
        $this->assertInstanceOf(TestDb::class, $current, "Class are an instance os Test");
        $current->id;
        $serialized = serialize($current);
        $unserialized = unserialize($serialized);
        $this->assertEquals($current, $unserialized);
        $unserialized->test = 'nuevo';
        $unserialized->save();
        $this->assertEquals(TestDb::findByPk($unserialized->getPrimaryKeyValue()), $unserialized);
        $unserialized->test = 'value';
        $unserialized->save();
    }

    public function testDelete()
    {
        $remover = TestDb::find(array('test' => 'value'));
        $this->assertEquals($this->loops, $remover->size(), "delete {$remover->size()} results");
        foreach ($remover as $remo) {
            $remo->remove();
        }
        $remover = TestDb::find(array('test' => 'value'));
        $this->assertEquals(0, $remover->size(), "No elements for delete after remove");
        $this->assertFalse($remover->hasElements(), "No elements for delete after remove");
//        $removeds = $this->db->delete(array('test' => 'value'));
//        $this->assertEquals($this->loops, $removeds, "delete {$removeds} results");
    }

    public function testTruncate()
    {
        $cursor = $this->db->select();
        $number = $this->db->getNResults();
        $success = $this->db->truncate();
        $this->assertEquals($number, $success, "Trucate table");
    }

    public function testModelCache()
    {
        $this->testInsert();
        $start = microtime(true) * 10000;
        $obj = TestDb::findByPk(1);
        $intermediate = microtime(true) * 10000;
        $obj2 = TestDb::findByPk(1);
        $final = microtime(true) * 10000;
        $this->assertEquals($obj, $obj2);
        echo PHP_EOL;
        echo $intermediate - $start;
        echo PHP_EOL;
        echo $final - $intermediate;
        echo PHP_EOL;
        $this->assertTrue(($final - $intermediate) < ($intermediate - $start), "Test better time for cached elements {$final} - {$intermediate} <= {$intermediate} - {$start}");
        $this->testRestart();
    }

    public function testDisconnect()
    {
        $result = $this->db->disconnect();
        $this->assertTrue($result, "Test disconnect");
    }

}

//class Connection extends Database {
//
//    public $typeDB;
//
//    public function selectConnection($typeDB = Model::SERVER) {
//        if (empty(static::$conn) || empty($this->typeDB) || $this->typeDB != $typeDB) {
//            $this->typeDB = $typeDB;
//            switch ($this->typeDB) {
//
//                case Database::TYPE_MYSQLI:
//                    $this->host = "localhost";
//                    $this->username = "root";
//                    $this->password = "";
//                    $this->dataBase = "test";
//                    break;
//
//                case Database::TYPE_MYSQL:
//                    $this->host = "localhost";
//                    $this->username = "root";
//                    $this->password = "";
//                    $this->dataBase = "test";
//                    break;
//
//                case Database::TYPE_MYSQLE:
//                    $this->host = "localhost";
//                    $this->username = "root";
//                    $this->password = "";
//                    $this->dataBase = "test";
//                    break;
//
//                case Database::TYPE_MARIADB:
//                    $this->host = "localhost";
//                    $this->username = "root";
//                    $this->password = "";
//                    $this->dataBase = "test";
//                    break;
//
//                case Database::TYPE_ORACLE:
//                    $this->host = "oracle-server/XE";
//                    $this->username = "test";
//                    $this->password = "test";
//                    $this->dataBase = "test";
//                    break;
//
//                case Database::TYPE_MSSQL:
//                    $this->host = 'mssql-server\SQLEXPRESS';
//                    $this->username = "sa";
//                    $this->password = "admin";
//                    $this->dataBase = "test";
//                    break;
//
//                case Database::TYPE_SQLSRV:
//                    $this->host = 'mssql-server\SQLEXPRESS';
//                    $this->username = "sa";
//                    $this->password = "admin";
//                    $this->dataBase = "test";
//                    break;
//
//                case Database::TYPE_SQLITE:
//                    $this->host = "./";
//                    $this->username = "";
//                    $this->password = "";
//                    $this->dataBase = "test.db";
//                    break;
//
//                case Database::TYPE_MONGO:
//                case Database::TYPE_MONGOCLIENT:
////                $this->host = "mongodb-server";
//                    $this->host = "localhost";
//                    $this->username = "test";
//                    $this->password = "test";
//                    $this->dataBase = "test";
//                    break;
//
////            case Database::TYPE_MONGO:
////                $this->host = "mongodb-server";
////                $this->username = "test";
////                $this->password = "test";
////                $this->dataBase = "test";
////                break;
//
//                case Database::TYPE_POSTGRE:
//                    $this->host = "192.168.0.15";
//                    $this->username = "postgres";
//                    $this->password = "admin";
//                    $this->dataBase = "test";
//                    break;
//            }
//        }
//        $this->connection();
//    }
//
//    public function connection() {
//        return parent::init($this->typeDB, array("host" => $this->host, "username" => $this->username, "password" => $this->password, "database" => $this->dataBase), $this->getTableName());
//    }
//
//    public function getTableName() {
//        return $this->table;
//    }
//
//    public function __wakeup() {
//        $this->selectConnection();
//    }
//
//}
//
//abstract class Model extends Connection {
//
//    const SERVER = Database::TYPE_MYSQLI;
//    const SERVERS = [
//        Database::TYPE_MYSQLI => Database::TYPE_MYSQLI,
//        Database::TYPE_SQLITE => Database::TYPE_SQLITE,
//        Database::TYPE_MONGO => Database::TYPE_MONGO,
//        Database::TYPE_MONGOCLIENT => Database::TYPE_MONGOCLIENT
//    ];
//    const CACHE = CacheAdapter::MODE_FILE;
//    const CACHES = [
//        CacheAdapter::MODE_FILE => '',
//        CacheAdapter::MODE_MEMCACHE => '127.0.0.1:11211',
//        CacheAdapter::MODE_MEMCACHED => '127.0.0.1:11211',
//        CacheAdapter::MODE_REDIS => '127.0.0.1:6379',
//    ];
//
//    private static $cache;
//    protected static $useCache = false;
//
//    public function __construct($id = null) {
//        $this->selectConnection(self::SERVERS[self::SERVER]);
//        if (intval($id) > 0) {
//            return static::findByPk($id, method_exists($this, 'relations'));
//        }
//        return $this;
//    }
//
//    public static function findByPk($id, $withRelations = true) {
//        if (static::$useCache) {
//            $key = static::getInstance()->getCacheKey($id);
//            $obj = static::getCache()->get($key);
//            if (!is_object($obj)) {
//                $obj = parent::findByPk($id, $withRelations);
//                static::getCache()->set($key, $obj, 10);
//            }
//            return $obj;
//        }
//        return parent::findByPk($id, $withRelations);
//    }
//
//    protected static function getCache() {
//        if (empty(self::$cache)) {
//            self::$cache = new CacheAdapter(self::CACHE, empty(self::CACHES[self::CACHE]) ? Core::getTempDir() : self::CACHES[self::CACHE]);
//        }
//        return self::$cache;
//    }
//
//    protected function getCacheKey() {
//        $return = parent::model();
//        if (count($this->columns()) > 0) {
//            $return .= implode('-', $this->columns());
//        }
//        if (count(func_get_args()) > 0) {
//            $return .= implode('-', func_get_args());
//        }
//        return $return;
//    }
//
//    public function save() {
//        $saved = parent::save();
//        if (static::$useCache) {
//            $key = $this->getCacheKey($this->getPrimaryKeyValue());
//            if (($obj = static::getCache()->get($key)) === false) {
//                $obj = parent::findByPk($this->getPrimaryKeyValue());
//                self::getCache()->set($key, $obj, 10);
//            } else {
//                self::getCache()->replace($key, $this);
//            }
//        }
//        return $saved;
//    }
//
//    public function remove() {
//        if (static::$useCache) {
//            $key = $this->getCacheKey($this->getPrimaryKeyValue());
//            static::getCache()->delete($key);
//        }
//        return parent::remove();
//    }
//
//}

class CachedConection extends \remote\database\datamodel\ModelCache
{

//    const SERVER = DatabaseAdapter::TYPE_SQLITE;
//    const SERVERS = [
//        DatabaseAdapter::TYPE_MYSQLI => DatabaseAdapter::TYPE_MYSQLI,
//        DatabaseAdapter::TYPE_SQLITE => DatabaseAdapter::TYPE_SQLITE,
//        DatabaseAdapter::TYPE_MONGO => DatabaseAdapter::TYPE_MONGO,
//        DatabaseAdapter::TYPE_MONGOCLIENT => DatabaseAdapter::TYPE_MONGOCLIENT
//    ];
    const CACHE = CacheAdapter::MODE_SESSION;
    const CACHES = [
        CacheAdapter::MODE_FILE => '',
        CacheAdapter::MODE_SESSION => 'qwertyasdf',
        CacheAdapter::MODE_MEMCACHE => '127.0.0.1:11211',
        CacheAdapter::MODE_MEMCACHED => '127.0.0.1:11211',
        CacheAdapter::MODE_REDIS => '127.0.0.1:6379',
    ];

//    public $typeDB;
//    protected static $conn;
    private static $cache;

//    public function __construct($id = null)
//    {
////        $this->getConnection();
//        if (intval($id) > 0) {
//            return static::findByPk($id, method_exists($this, 'relations'));
//        }
//        return $this;
//    }
//
//    public function setConnection($typeDB = self::SERVER)
//    {
//        if (empty(static::$conn) || empty($this->typeDB) || $this->typeDB != $typeDB) {
//            $this->typeDB = $typeDB;
//            switch ($this->typeDB) {
//
//                case DatabaseAdapter::TYPE_MYSQLI:
//                    $this->host = "localhost";
//                    $this->username = "root";
//                    $this->password = "";
//                    $this->dataBase = "test";
//                    break;
//
//                case DatabaseAdapter::TYPE_MYSQL:
//                    $this->host = "localhost";
//                    $this->username = "root";
//                    $this->password = "";
//                    $this->dataBase = "test";
//                    break;
//
//                case DatabaseAdapter::TYPE_MYSQLE:
//                    $this->host = "localhost";
//                    $this->username = "root";
//                    $this->password = "";
//                    $this->dataBase = "test";
//                    break;
//
//                case DatabaseAdapter::TYPE_MARIADB:
//                    $this->host = "localhost";
//                    $this->username = "root";
//                    $this->password = "";
//                    $this->dataBase = "test";
//                    break;
//
//                case DatabaseAdapter::TYPE_ORACLE:
//                    $this->host = "oracle-server/XE";
//                    $this->username = "test";
//                    $this->password = "test";
//                    $this->dataBase = "test";
//                    break;
//
//                case DatabaseAdapter::TYPE_MSSQL:
//                    $this->host = 'mssql-server\SQLEXPRESS';
//                    $this->username = "sa";
//                    $this->password = "admin";
//                    $this->dataBase = "test";
//                    break;
//
//                case DatabaseAdapter::TYPE_SQLSRV:
//                    $this->host = 'mssql-server\SQLEXPRESS';
//                    $this->username = "sa";
//                    $this->password = "admin";
//                    $this->dataBase = "test";
//                    break;
//
//                case DatabaseAdapter::TYPE_SQLITE:
//                    $this->host = "./";
//                    $this->username = "";
//                    $this->password = "";
//                    $this->dataBase = "test.db";
//                    break;
//
//                case DatabaseAdapter::TYPE_MONGO:
//                case DatabaseAdapter::TYPE_MONGOCLIENT:
////                $this->host = "mongodb-server";
//                    $this->host = "localhost";
//                    $this->username = "test";
//                    $this->password = "test";
//                    $this->dataBase = "test";
//                    break;
//
////            case DatabaseAdapter::TYPE_MONGO:
////                $this->host = "mongodb-server";
////                $this->username = "test";
////                $this->password = "test";
////                $this->dataBase = "test";
////                break;
//
//                case DatabaseAdapter::TYPE_POSTGRE:
//                    $this->host = "192.168.0.15";
//                    $this->username = "postgres";
//                    $this->password = "admin";
//                    $this->dataBase = "test";
//                    break;
//            }
//        }
//    }

    protected function getConnection()
    {
        $this->setConnection(static::SERVER);
//            return new DatabaseAdapter($this->typeDB, array("host" => $this->host, "username" => $this->username, "password" => $this->password, "database" => $this->dataBase), $this->getTableName());//)->getResource();
        if (empty(self::$conn)) {
            self::$conn = new DatabaseAdapter($this->typeDB, array("host" => $this->host, "username" => $this->username, "password" => $this->password, "database" => $this->dataBase)); //)->getResource();
            self::$conn = self::$conn->getResource();
        }
        return self::$conn;
    }

    protected static function getCache()
    {
        if (empty(self::$cache)) {
            self::$cache = new CacheAdapter(self::CACHE, empty(self::CACHES[self::CACHE]) ? Core::getTempDir() : self::CACHES[self::CACHE]); //)->getResource();
        }
        return self::$cache;
    }

    protected function getCacheKey(...$args)
    {
        $return = parent::model();
        if (count($this->columns()) > 0) {
            $return .= implode('-', $this->columns());
        }
        if (count(func_get_args()) > 0) {
            $return .= implode('-', func_get_args());
        }
        return $return;
    }

    public function getTableName()
    {
        return $this->table;
    }

    protected function getCacheTime()
    {
        return 60;
    }

//
//    public function __wakeup() {
//        $this->getConnection();
//    }
}

class TestDb extends \remote\database\datamodel\Model
{

    protected $table = 'test';
    public function getTableName()
    {
        return $this->table;
    }

}
