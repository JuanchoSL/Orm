<?php

namespace JuanchoSL\Orm\engine;

enum Engines
{
    /*
    case TYPE_MYSQL = 'MYSQL';
    case TYPE_MARIADB = 'MARIADB';
    case TYPE_MYSQLE = 'MYSQLE';
    */
    case TYPE_MYSQLI; //= 'MYSQLI';
    case TYPE_SQLITE; // = 'SQLITE';
    case TYPE_POSTGRE; // = 'POSTGRE';
    case TYPE_SQLSRV; // = 'SQLSRV';
    case TYPE_ORACLE; // = 'ORACLE';
    case TYPE_ODBC; // = 'ODBC';
    case TYPE_DB2;// = 'DB2';
    /*
    case TYPE_MSSQL = 'MSSQL';
        case TYPE_MSQL = 'MSQL';
        case TYPE_ELASTICSEARCH;// = 'ELASTICSEARCH';
        */
    case TYPE_MONGO; // = 'MONGO';
    case TYPE_MONGOCLIENT; // = 'MONGOCLIENT';

    public function string(): string
    {
        return match ($this) {
            Engines::TYPE_MYSQLI => 'MYSQLI',
            Engines::TYPE_SQLITE => 'SQLITE',
            Engines::TYPE_POSTGRE => 'POSTGRE',
            Engines::TYPE_SQLSRV => 'SQLSRV',
            Engines::TYPE_ORACLE => 'ORACLE',
            Engines::TYPE_ODBC => 'ODBC',
            Engines::TYPE_DB2 => 'DB2',
        };
    }
}