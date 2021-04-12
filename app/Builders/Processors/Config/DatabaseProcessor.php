<?php

namespace App\Builders\Processors\Config;

use Closure;
use App\Builders\Processors\PHPBuilderProcessor;
use App\Builders\Helpers\Mutations\DatabaseMutationHelpers;
use App\Builders\PHP\Laravel\Framework\Config\DatabaseBuilder;

/**
 * Class DatabaseProcessor
 * @package App\Builders\Processors\Config
 */
class DatabaseProcessor extends PHPBuilderProcessor
{
    /**
     * @param $builder
     * @param Closure $next
     * @return bool
     */
    public function process($builder, Closure $next): bool
    {
        $this->processConfig($builder);

        $next($builder);

        return true;
    }

    /**
     * @param DatabaseBuilder $builder
     * @return void
     */
    private function processConfig(DatabaseBuilder $builder): void
    {
        $dbConfig = app('mutations')->for('database');

        $dbConnection = $dbConfig['connection'];

        $path = $dbConfig['path'];

        $fkConstraints = $dbConfig['fkConstraints'];

        $host = $dbConfig['host'];
        $port = $dbConfig['port'];
        $username = $dbConfig['username'];
        $db = $dbConfig['database'];

        $charset = $dbConfig['charset'];
        $collation = $dbConfig['collation'];
        $prefix = $dbConfig['prefix'];
        $prefixIndexes = $dbConfig['prefixIndexes'];
        $strict = $dbConfig['strict'];

        // PgSQL
        $schema = $dbConfig['schema'];
        $sslMode = $dbConfig['sslMode'];

        // MongoDB
        $dbAuthDatabase = $dbConfig['dbAuthDatabase'];

        if ($dbConnection === 'sqlite') {
            $builder->setSQLiteConfigKey($path, $db, $prefix, $fkConstraints);
        }

        if ($dbConnection === 'mysql') {
            $builder->setMysqlConfigKey($db, $username, '', $host, $port, $charset, $collation, $prefix, '', $prefixIndexes, $strict);
        }

        if ($dbConnection === 'postgresql') {
            $charset = strtolower($charset);
            $builder->setPgSqlConfigKey($db, $username, '', $host, $port, $charset, $prefix, '', $prefixIndexes, $schema, $sslMode);
        }

        if ($dbConnection === 'mongodb') {
            $replicaSet = $dbConfig['replicaSet'];
            $hosts = count($dbConfig['hosts']) ? $dbConfig['hosts'] : $host;
            $builder->setMongoDBConfigKey($replicaSet, $db, $username, $hosts, $port, $dbAuthDatabase);
        }

        if ($dbConnection === 'sqlserver') {
            $builder->setSqlServerConfigKey($db, $username, '', $host, $port, $charset, $prefix, $prefixIndexes);
        }

        $builder->setDefaultDatabaseConnection(DatabaseMutationHelpers::getConnectionName($dbConnection));

    }
}
