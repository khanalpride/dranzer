<?php

namespace App\Builders\PHP\Laravel\Framework\Config;

use Illuminate\Support\Str;
use App\Builders\PHP\FileBuilder;
use PhpParser\Node\Expr\ArrayItem;
use App\Builders\Processors\Config\DatabaseProcessor;

/**
 * Class DatabaseBuilder
 * @package App\Builders\PHP\Laravel\Framework\Config
 */
class DatabaseBuilder extends FileBuilder
{
    /**
     * @var array|string[]
     */
    protected array $processors = [
        DatabaseProcessor::class,
    ];
    /**
     * @var string
     */
    protected string $filename = 'database.php';
    /**
     * @var string
     */
    private $defaultDatabaseConnection = 'mysql';
    /**
     * @var
     */
    private $sQLiteConfig;
    /**
     * @var
     */
    private $mySQLConfig;
    /**
     * @var
     */
    private $mongoDBConfig;
    /**
     * @var
     */
    private $pgSqlConfig;
    /**
     * @var
     */
    private $sqlServerConfig;

    /**
     * @return DatabaseBuilder
     */
    public function prepare(): DatabaseBuilder
    {
        return $this->useStr();
    }

    /**
     * @return DatabaseBuilder
     */
    private function useStr(): DatabaseBuilder
    {
        $this->use(Str::class);

        return $this;
    }

    /**
     * @return bool
     */
    public function build(): bool
    {
        return $this
            ->buildConfigArray()
            ->toDisk();
    }

    /**
     * @return DatabaseBuilder
     */
    private function buildConfigArray(): DatabaseBuilder
    {
        $this->retArr([
            $this->getDefaultKey(),
            $this->getConnectionsKey(),
            $this->getMigrationsKey(),
            $this->getRedisKey()
        ]);
        return $this;
    }

    /**
     * @param $driver
     * @param $database
     * @param array $additionalKeys
     * @param null $host
     * @param null $port
     * @param null $username
     * @param null $password
     * @param null $charset
     * @param null $unixSocket
     * @param null $prefix
     * @param null $prefixIndexes
     * @param null $strict
     * @param bool $useUnixSocket
     * @return ArrayItem
     * @noinspection DuplicatedCode
     */
    private function createDatabaseConfigBlock($driver, $database, $additionalKeys = [], $host = null, $port = null, $username = null, $password = null, $charset = null, $unixSocket = null, $prefix = null, $prefixIndexes = null, $strict = null, $useUnixSocket = true): ArrayItem
    {
        $config = [
            'driver'   => $driver,
            'database' => $database
        ];
        $host !== null && !array_key_exists('host', $additionalKeys) && $config['host'] = $host;
        $port !== null && !array_key_exists('port', $additionalKeys) && $config['port'] = $this->int($port);
        $username !== null && !array_key_exists('username', $additionalKeys) && $config['username'] = $username;
        $password !== null && !array_key_exists('password', $additionalKeys) && $config['password'] = $password;

        $charset !== null && !array_key_exists('charset', $additionalKeys) && $config['charset'] = $charset;
        $useUnixSocket && $unixSocket !== null && !array_key_exists('unix_socket', $additionalKeys) && $config['unix_socket'] = $unixSocket;
        $prefix !== null && !array_key_exists('prefix', $additionalKeys) && $config['prefix'] = $prefix;
        $prefixIndexes !== null && !array_key_exists('prefix_indexes', $additionalKeys) && $config['prefix_indexes'] = $prefixIndexes;
        $strict !== null && !array_key_exists('strict', $additionalKeys) && $config['strict'] = $strict;

        $config = array_merge($config, $additionalKeys);

        // Handle MongoDB Hosts (Array) (Special Case)
        $hostsNodes = [];
        if (is_array($config['host'] ?? null)) {
            $mongoDBHosts = $config['host'];
            foreach ($mongoDBHosts as $mongoDBHost) {
                $hostsNodes[] = $this->string($mongoDBHost['name'] ?? '');
            }
        }

        $fetchFromEnv = [
            'port'        => 'DB_PORT',
            'database'    => 'DB_DATABASE',
            'username'    => 'DB_USERNAME',
            'password'    => 'DB_PASSWORD',
            'unix_socket' => 'DB_SOCKET',
        ];

        if (!is_array($config['host'] ?? null)) {
            $fetchFromEnv['host'] = 'DB_HOST';
        } else {
            $config['host'] = $this->arr($hostsNodes);
        }

        $keys = [];

        foreach ($config as $key => $value) {
            if (array_key_exists($key, $fetchFromEnv)) {
                $value = $this->envFuncCall($fetchFromEnv[$key], [is_string($value) ? $this->string($value) : $value]);
            }

            if (is_array($value)) {
                $converted = [];
                foreach ($value as $k => $v) {
                    $v = $this->tryConstCasting($v);
                    if (is_int($k)) {
                        $converted[] = $v;
                    } else {
                        $converted[$k] = $v;
                    }
                }
                $value = is_array($converted) ? $converted : $this->arr($converted);
            }

            $value = $this->tryConstCasting($value);

            $keys[] = is_array($value) ? $value : $this->assoc($key, $value);
        }

        return $this->assoc($driver, $this->arr($keys));
    }

    /**
     * @param $name
     * @param $database
     * @param array $envOverrides
     * @return ArrayItem
     */
    private function createRedisConnectionBlock($name, $database, $envOverrides = []): ArrayItem
    {
        // When fetching an env variable, the default is null so make sure
        // we set the value to nop to avoid any value being set.
        $password = $this->nopExpr();

        $config = [
            'url'      => null,
            'host'     => '127.0.0.1',
            'password' => $password,
            'port'     => $this->int(6379),
            'database' => is_int($database) ? $this->int($database) : $database
        ];

        $fetchFromEnv = [
            'url'      => $envOverrides['url'] ?? 'REDIS_URL',
            'host'     => $envOverrides['host'] ?? 'REDIS_HOST',
            'port'     => $envOverrides['port'] ?? 'REDIS_PORT',
            'database' => $envOverrides['database'] ?? 'REDIS_DATABASE',
            'password' => $envOverrides['password'] ?? 'REDIS_PASSWORD',
        ];

        $keys = [];

        foreach ($config as $key => $value) {
            if (array_key_exists($key, $fetchFromEnv)) {
                if ($value) {
                    $value = $this->envFuncCall($fetchFromEnv[$key], [is_string($value) ? $this->string($value) : $value]);
                } else {
                    $value = $this->envFuncCall($fetchFromEnv[$key]);
                }
            }

            if (is_array($value)) {
                $converted = [];
                foreach ($value as $k => $v) {
                    $v = $this->tryConstCasting($v);
                    if (is_int($k)) {
                        $converted[] = $v;
                    } else {
                        $converted[$k] = $v;
                    }
                }
                $value = $this->arr($converted);
            }

            $value = $this->tryConstCasting($value);

            $keys[] = $this->assoc($key, $value);
        }

        return $this->assoc($name, $this->arr($keys));
    }

    /**
     * @param $path
     * @param $database
     * @param $prefix
     * @param $fkConstraints
     *
     * @noinspection PhpUnused
     * @noinspection UnknownInspectionInspection
     */
    public function setSqliteConfigKey($path, $database, $prefix, $fkConstraints): void
    {
        $this->sQLiteConfig = $this->createDatabaseConfigBlock('sqlite', $this->funcCall('database_path', [$this->string($database)]), [
            'url'                     => $this->funcCall('env', [
                $this->string('DATABASE_URL'),
                $path ? $this->string($path) : $this->nopExpr(),
            ]),
            'foreign_key_constraints' => $this->funcCall('env', [
                $this->string('DB_FOREIGN_KEYS'),
                $this->const($fkConstraints ? 'true' : 'false'),
            ])
        ], null, null, null, null, null, null, $prefix);
    }

    /**
     * @param string $database
     * @param string $username
     * @param string $password
     * @param string $host
     * @param int $port
     * @param string $charset
     * @param string $collation
     * @param string $prefix
     * @param string $unixSocket
     * @param bool $prefixIndexes
     * @param bool $strict
     * @param null $engine
     *
     * @noinspection PhpUnused
     * @noinspection UnknownInspectionInspection
     *
     */
    public function setMysqlConfigKey($database = 'forge', $username = 'forge', $password = '', $host = '127.0.0.1', $port = 3306, $charset = 'utf8mb4', $collation = 'utf8mb4_unicode_ci', $prefix = '', $unixSocket = '', $prefixIndexes = true, $strict = true, $engine = null): void
    {
        $this->mySQLConfig = $this->createDatabaseConfigBlock('mysql', $database, [
            'collation' => $this->string($collation),
            'engine'    => $this->tryConstCasting($engine)
        ], $host, $port, $username, $password, $charset, $unixSocket, $prefix, $this->const($prefixIndexes), $this->const($strict));
    }

    /**
     * @param string $database
     * @param string $username
     * @param string $password
     * @param string $host
     * @param int $port
     * @param string $charset
     * @param string $prefix
     * @param string $unixSocket
     * @param bool $prefixIndexes
     * @param string $schema
     * @param string $sslMode
     *
     * @noinspection PhpUnused
     * @noinspection UnknownInspectionInspection
     *
     */
    public function setPgSqlConfigKey($database = 'forge', $username = 'forge', $password = '', $host = '127.0.0.1', $port = 3306, $charset = 'utf8mb4', $prefix = '', $unixSocket = '', $prefixIndexes = true, $schema = 'public', $sslMode = 'prefer'): void
    {
        /** @noinspection SpellCheckingInspection */
        $this->pgSqlConfig = $this->createDatabaseConfigBlock('pgsql', $database, [
            'schema'  => $this->string($schema),
            'sslmode' => $this->string($sslMode),
        ], $host, $port, $username, $password, $charset, $unixSocket, $prefix, $prefixIndexes, null, false);
    }

    /**
     * @param $replicaSet
     * @param string $database
     * @param string $username
     * @param string $host
     * @param int $port
     * @param string $dbAuthenticationDatabase
     */
    public function setMongoDBConfigKey($replicaSet, $database = 'forge', $username = 'forge', $host = '127.0.0.1', $port = 27017, $dbAuthenticationDatabase = 'admin'): void
    {
        $options = [];

        $dbOption = $this->assoc('database', $this->funcCall('env', [
            $this->string('DB_AUTHENTICATION_DATABASE'),
            $this->string($dbAuthenticationDatabase)
        ]));

        $options[] = $dbOption;

        if ($replicaSet) {
            $replicaSetOption = $this->assoc('replicaSet', $this->string($replicaSet));
            $options[] = $replicaSetOption;
        }

        $options = [
            'options' => $this->arr($options)
        ];

        $this->mongoDBConfig = $this->createDatabaseConfigBlock('mongodb', $database, $options, $host, $port, $username);
    }

    /**
     * @param string $database
     * @param string $username
     * @param string $password
     * @param string $host
     * @param int $port
     * @param string $charset
     * @param string $prefix
     * @param bool $prefixIndexes
     */
    public function setSqlServerConfigKey($database = 'forge', $username = 'forge', $password = '', $host = '127.0.0.1', $port = 1433, $charset = 'utf8', $prefix = '', $prefixIndexes = true): void
    {
        /** @noinspection SpellCheckingInspection */
        $this->sqlServerConfig = $this->createDatabaseConfigBlock('sqlsrv', $database, [], $host, $port, $username, $password, $charset, null, $prefix, $prefixIndexes);
    }

    /**
     * @return string
     */
    public function getDefaultDatabaseConnection(): string
    {
        return $this->defaultDatabaseConnection;
    }

    /**
     * @param string $defaultDatabaseConnection
     * @return $this
     */
    public function setDefaultDatabaseConnection(string $defaultDatabaseConnection): DatabaseBuilder
    {
        $this->defaultDatabaseConnection = $defaultDatabaseConnection;
        return $this;
    }

    /**
     * @param $value
     * @return mixed
     *
     * @noinspection PhpReturnDocTypeMismatchInspection
     */
    private function tryConstCasting($value)
    {
        if ($value === null || $value === 'null') {
            return $this->const('null');
        }

        if ($value === true || $value === 'true') {
            return $this->const('true');
        }

        if ($value === false || $value === 'false') {
            return $this->const('false');
        }

        return $value;
    }

    /**
     * @return ArrayItem
     */
    private function getDefaultKey(): ArrayItem
    {
        return $this->assoc(
            'default',
            $this->envFuncCall('DB_CONNECTION', [$this->string($this->getDefaultDatabaseConnection())]),
            'Default Database Connection Name',
            'Here you may specify which of the database connections below you wish to use as your default connection for all database work. Of course you may use many connections at once using the Database library.'
        );
    }

    /**
     * @return ArrayItem
     */
    private function getConnectionsKey(): ArrayItem
    {
        return $this->assoc('connections', $this->arr([
            $this->getSqliteConfigKey(),
            $this->getMySqlConfigKey(),
            $this->getMongoDBConfigKey(),
            $this->getPgSqlConfigKey(),
            $this->getSqlServerConfigKey(),
        ]), 'Database Connections', 'Here are each of the database connections setup for your application. Of course, examples of configuring each database platform that is supported by Laravel is shown below to make development simple. All database work in Laravel is done through the PHP PDO facilities so make sure you have the driver for your particular database of choice installed on your machine before you begin development.');
    }

    /**
     * @return ArrayItem
     */
    private function getSqliteConfigKey(): ArrayItem
    {
        return $this->sQLiteConfig ?? $this->createDatabaseConfigBlock('sqlite', $this->funcCall('database_path', [$this->string('database.sqlite')]), [
                'url'                     => $this->funcCall('env', [
                    $this->string('DATABASE_URL'),
                    $this->const('null'),
                ]),
                'foreign_key_constraints' => $this->funcCall('env', [
                    $this->string('DB_FOREIGN_KEYS'),
                    $this->const('true'),
                ])
            ], '');
    }

    /**
     * @return ArrayItem
     */
    private function getMySqlConfigKey(): ArrayItem
    {
        return $this->mySQLConfig ?? $this->createDatabaseConfigBlock('mysql', 'forge', [
                'collation' => $this->string('utf8mb4_unicode_ci'),
                'engine'    => null
            ], '127.0.0.1', 3306, 'forge', '', 'utf8mb4', '', '', $this->const(true), $this->const(true));
    }

    /**
     * @return ArrayItem
     */
    private function getMongoDBConfigKey(): ArrayItem
    {
        return $this->mongoDBConfig ?? $this->createDatabaseConfigBlock('mongodb', 'forge', [
                'options' => $this->arr([
                    'database' => $this->assoc('database', $this->funcCall('env', [
                        $this->string('DB_AUTHENTICATION_DATABASE'),
                        $this->string('admin')
                    ]))
                ])
            ], '127.0.0.1', '27017', 'forge');
    }

    /**
     * @return ArrayItem
     */
    private function getPgSqlConfigKey(): ArrayItem
    {
        /** @noinspection SpellCheckingInspection */
        return $this->pgSqlConfig ?? $this->createDatabaseConfigBlock('pgsql', 'forge', [
                'schema'  => $this->string('public'),
                'sslmode' => $this->string('prefer'),
            ], '127.0.0.1', 5432, 'forge', '', 'utf8', null, '', true, null, false);
    }

    /** @noinspection DuplicatedCode */

    /**
     * @return ArrayItem
     * @noinspection SpellCheckingInspection
     */
    private function getSqlServerConfigKey(): ArrayItem
    {
        return $this->sqlServerConfig ?? $this->createDatabaseConfigBlock('sqlsrv', 'forge', [], '127.0.0.1', 1433, 'forge', '', 'utf8', null, '', true);
    }

    /**
     * @return ArrayItem
     */
    private function getMigrationsKey(): ArrayItem
    {
        return $this->assoc('migrations', 'migrations', 'Migration Repository Table', 'This table keeps track of all the migrations that have already run for your application. Using this information, we can determine which of the migrations on disk haven\'t actually been run in the database.');
    }

    /**
     * @return ArrayItem
     */
    private function getRedisKey(): ArrayItem
    {
        $defaultConnection = $this->createRedisConnectionBlock('default', 0);
        $cacheConnection = $this->createRedisConnectionBlock('cache', 1, ['database' => 'REDIS_CACHE_DB']);

        /** @noinspection SpellCheckingInspection */
        return $this->assoc('redis', $this->arr([
            $this->assoc('client', 'phpredis'),
            $this->assoc('options', $this->arr([
                $this->assoc('cluster', $this->envFuncCall('REDIS_CLUSTER', [$this->string('redis')])),
                $this->assoc('prefix', $this->envFuncCall('REDIS_PREFIX', [
                    $this->concat(
                        $this->staticCall('Str', 'slug', [
                            $this->envFuncCall('APP_NAME', [$this->string('laravel')]),
                            $this->string('_')
                        ]),
                        $this->string('_database_')
                    )
                ])),
            ])),
            $defaultConnection,
            $cacheConnection
        ]), 'Redis Databases', 'Redis is an open source, fast, and advanced key-value store that also provides a richer body of commands than a typical key-value system such as APC or Memcached. Laravel makes it easy to dig right in.');
    }

}
