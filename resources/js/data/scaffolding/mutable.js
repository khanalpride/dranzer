export default [
  {
    key: 'Database',
    children: [
      {
        key: 'Selector',
        children: [
          {
            key: 'SQLiteConfiguration',
            paths: [
              {
                key: 'Path',
                name: 'Database Path',
                path: 'database/sqlite/db_path',
              },
              {
                key: 'Name',
                name: 'Database Name',
                path: 'database/sqlite/db_name',
              },
              {
                key: 'Prefix',
                name: 'Table Prefix',
                path: 'database/sqlite/db_table_prefix',
              },
              {
                key: 'Memory',
                name: 'In-Memory Database',
                path: 'database/sqlite/in_memory',
              },
              {
                key: 'Constraints',
                name: 'Foreign Key Constraints',
                path: 'database/sqlite/fk_constraints',
              },
            ],
          },
          {
            key: 'MySQLConfiguration',
            paths: [
              {
                key: 'URL',
                name: 'Database URL',
                path: 'database/mysql/db_url',
              },
              {
                key: 'Host',
                name: 'Database Host',
                path: 'database/mysql/db_host',
              },
              {
                key: 'Port',
                name: 'Database Port',
                path: 'database/mysql/db_port',
              },
              {
                key: 'Name',
                name: 'Database Name',
                path: 'database/mysql/db_name',
              },
              {
                key: 'Username',
                name: 'Database Username',
                path: 'database/mysql/db_username',
              },
              {
                key: 'TablePrefix',
                name: 'Table Prefix',
                path: 'database/mysql/db_table_prefix',
              },
              {
                key: 'Charset',
                name: 'Charset',
                path: 'database/mysql/db_charset',
              },
              {
                key: 'Collation',
                name: 'Collation',
                path: 'database/mysql/db_collation',
              },
              {
                key: 'PrefixIndexes',
                name: 'Prefix Indexes',
                path: 'database/mysql/db_prefix_indexes',
              },
              {
                key: 'StrictMode',
                name: 'Strict Mode',
                path: 'database/mysql/db_strict_mode',
              },
            ],
          },
          {
            key: 'MongoDBConfiguration',
            paths: [
              {
                key: 'Host',
                name: 'Database Host',
                path: 'database/mongodb/db_host',
              },
              {
                key: 'Port',
                name: 'Database Port',
                path: 'database/mongodb/db_port',
              },
              {
                key: 'Name',
                name: 'Database Name',
                path: 'database/mongodb/db_name',
              },
              {
                key: 'Username',
                name: 'Database Username',
                path: 'database/mongodb/db_username',
              },
              {
                key: 'AuthDatabase',
                name: 'Authentication Database',
                path:
                                    'database/mongodb/db_authentication_database',
              },
              {
                key: 'ReplicaSet',
                name: 'Replica Set',
                path: 'database/mongodb/db_replica_set',
              },
              {
                key: 'MultipleHosts',
                name: 'Multiple Hosts',
                path: 'database/mongodb/db_multiple_hosts',
              },
            ],
          },
          {
            key: 'PostgreSQLConfiguration',
            paths: [
              {
                key: 'URL',
                name: 'Database URL',
                path: 'database/postgresql/db_url',
              },
              {
                key: 'Host',
                name: 'Database Host',
                path: 'database/postgresql/db_host',
              },
              {
                key: 'Port',
                name: 'Database Port',
                path: 'database/postgresql/db_port',
              },
              {
                key: 'Name',
                name: 'Database Name',
                path: 'database/postgresql/db_name',
              },
              {
                key: 'Username',
                name: 'Database Username',
                path: 'database/postgresql/db_username',
              },
              {
                key: 'TablePrefix',
                name: 'Table Prefix',
                path: 'database/postgresql/db_table_prefix',
              },
              {
                key: 'PrefixIndexes',
                name: 'Prefix Indexes',
                path: 'database/postgresql/db_prefix_indexes',
              },
              {
                key: 'Charset',
                name: 'Charset',
                path: 'database/postgresql/db_charset',
              },
              {
                key: 'DatabaseSchema',
                name: 'Database Schema',
                path: 'database/postgresql/db_schema',
              },
              {
                key: 'SSLMode',
                name: 'SSL Mode',
                path: 'database/postgresql/db_ssl_mode',
              },
            ],
          },
          {
            key: 'SQLServerConfiguration',
            paths: [
              {
                key: 'URL',
                name: 'Database URL',
                path: 'database/sqlserver/db_url',
              },
              {
                key: 'Host',
                name: 'Database Host',
                path: 'database/sqlserver/db_host',
              },
              {
                key: 'Port',
                name: 'Database Port',
                path: 'database/sqlserver/db_port',
              },
              {
                key: 'Name',
                name: 'Database Name',
                path: 'database/sqlserver/db_name',
              },
              {
                key: 'Username',
                name: 'Database Username',
                path: 'database/sqlserver/db_username',
              },
              {
                key: 'TablePrefix',
                name: 'Table Prefix',
                path: 'database/sqlserver/db_table_prefix',
              },
              {
                key: 'PrefixIndexes',
                name: 'Prefix Indexes',
                path: 'database/sqlserver/db_prefix_indexes',
              },
              {
                key: 'Charset',
                name: 'Charset',
                path: 'database/sqlserver/db_charset',
              },
            ],
          },
        ],
      },
    ],
  },

  {
    key: 'Mix',
    children: [
      {
        key: 'ResourcePath',
        name: 'Base Resource Path',
        path: 'assets/mix/baseResourcePath',
      },
      {
        key: 'BaseTemplatePath',
        name: 'Base Template Path',
        path: 'assets/mix/baseTemplatePath',
      },
      {
        key: 'BaseOutputPath',
        name: 'Base Output Path',
        path: 'assets/mix/baseOutputPath',
      },
      {
        key: 'JSModules',
        children: [
          {
            key: 'ModulesPath',
            name: 'JS Modules Path',
            path: 'assets/mix/jsModules/modulesPath',
          },
        ],
      },
      {
        key: 'HMR',
        children: [
          {
            key: 'Host',
            name: 'Host',
            path: 'assets/mix/hmr/host',
          },
          {
            key: 'Port',
            name: 'Port',
            path: 'assets/mix/hmr/port',
          },
        ],
      },
    ],
  },
  {
    key: 'Logging',
    children: [
      {
        key: 'Channels',
        children: [
          {
            key: 'Single',
            children: [
              {
                key: 'Path',
                name: 'Single Channel Path',
                path: 'logging/channels/single/path',
              },
            ],
          },
          {
            key: 'Daily',
            children: [
              {
                key: 'Path',
                name: 'Daily Channel Path',
                path: 'logging/channels/daily/path',
              },
              {
                key: 'Days',
                name: 'Daily Channel Days',
                path: 'logging/channels/daily/days',
              },
            ],
          },
          {
            key: 'Slack',
            children: [
              {
                key: 'WebhookURL',
                name: 'Slack Webhook URL',
                path: 'logging/channels/slack/webhook',
              },
              {
                key: 'Username',
                name: 'Slack Username',
                path: 'logging/channels/slack/username',
              },
              {
                key: 'Emoji',
                name: 'Slack Emoji',
                path: 'logging/channels/slack/emoji',
              },
            ],
          },
        ],
      },
    ],
  },
  {
    key: 'Deployment',
    children: [
      {
        key: 'Envoy',
        children: [
          {
            key: 'AppDirectory',
            name: 'App Directory',
            path: 'deployment/envoy/appDir',
          },
          {
            key: 'ReleasesDirectory',
            name: 'Releases Directory',
            path: 'deployment/envoy/releasesDir',
          },
        ],
      },
    ],
  },
];
