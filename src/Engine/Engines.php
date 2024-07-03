<?php

declare(strict_types=1);

namespace JuanchoSL\Orm\engine;

enum Engines: string
{

    case TYPE_MYSQLI = 'MYSQLI';
    case TYPE_SQLITE = 'SQLITE';
    case TYPE_POSTGRE = 'POSTGRE';
    case TYPE_SQLSRV = 'SQLSRV';
    case TYPE_ORACLE = 'ORACLE';
    case TYPE_ODBC = 'ODBC';
    case TYPE_DB2 = 'DB2';
    /*
    case TYPE_MSSQL = 'MSSQL';
        case TYPE_MSQL = 'MSQL';
        case TYPE_ELASTICSEARCH;// = 'ELASTICSEARCH';
        */
    case TYPE_MONGO = 'MONGO';
    case TYPE_MONGOCLIENT = 'MONGOCLIENT';

}