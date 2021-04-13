export default [
  {
    title: 'Application',
    children: [
      {
        title: 'Branding',
        key: 'BrandingManager',
      },
    ],
  },
  {
    title: 'Exceptions',
    children: [
      {
        title: 'Configure Exception Handler',
        key: 'ExceptionHandler',
      },
      {
        title: 'Configure Error Pages',
        key: 'ErrorPagesManager',
      },
    ],
  },
  {
    title: 'Layouts',
    children: [
      {
        title: 'Create Custom Layout (Blade)',
        key: 'UserInterfaceManager',
      },
      {
        title: 'Create Custom Blade Partials',
        key: 'CustomBladePartialsManager',
      },
      {
        title: 'Create Admin Panel',
        key: 'UserInterfaceManager',
      },
    ],
  },
  {
    title: 'Frontend',
    children: [
      {
        title: 'Configure Tailwind',
        key: 'TailwindManager',
      },
      {
        title: 'Setup Vue',
        key: 'VueManager',
      },
    ],
  },
  {
    title: 'Middleware',
    children: [
      {
        title: 'Configure Default Middlewares',
        key: 'MiddlewareConfiguration',
      },
    ],
  },
  {
    title: 'Security',
    children: [
      {
        title: 'Configure CSRF Exceptions',
        key: 'MiddlewareConfiguration',
      },
    ],
  },
  {
    title: 'Database',
    children: [
      {
        title: 'Select Database',
        key: 'DatabaseManager',
        routeKey: 'manage-database',
      },
      {
        title: 'Define Schema',
        key: 'SchemaManager',
        configurable: [
          {
            title: 'Table Definitions',
          },
          {
            title: 'Factories',
          },
          {
            title: 'Seeders',
          },
          {
            title: 'Eloquent Model Scopes',
          },
        ],
      },
    ],
  },
  {
    title: 'Controllers',
    children: [
      {
        title: 'Web Controllers',
        key: 'ControllersContainer',
        props: {
          type: 'web',
        },
        cache: false,
      },
    ],
  },
  {
    title: 'Validation',
    children: [
      {
        title: 'Manage Form Validation',
        key: 'FormRequestValidationContainer',
      },
    ],
  },
  {
    title: 'Mail',
    children: [
      {
        title: 'Manage Mailables',
        key: 'MailableManager',
      },
    ],
  },
  {
    title: 'Notifications',
    children: [
      {
        title: 'Manage Notifications',
        key: 'NotificationsManager',
      },
    ],
  },
  {
    title: 'Eloquent',
    children: [
      {
        title: 'Manage Relations',
        key: 'RelationsManager',
        routeKey: 'manage-relations',
      },
    ],
  },
  // {
  //   title: 'Localization',
  //   children: [
  //     {
  //       title: 'Translation Strings',
  //       key: 'TranslationStringsManager',
  //     },
  //   ],
  // },
  {
    title: 'Development',
    children: [
      {
        title: 'Laravel Decomposer',
        key: 'LaravelDecomposerManager',
      },
      {
        title: 'Laravel Debug Bar',
        key: 'LaravelDebugBarManager',
      },
      {
        title: 'Laravel IDE Helper',
        key: 'LaravelIdeHelperManager',
      },
    ],
  },
  {
    title: 'Logging',
    children: [
      {
        title: 'Configure Logging Driver',
        key: 'LoggingManager',
      },
      {
        title: 'Configure Telescope',
        key: 'TelescopeManager',
      },
    ],
  },
  {
    title: 'Requests',
    children: [
      {
        title: 'Headers and Proxies',
        key: 'TrustProxiesManager',
      },
    ],
  },
  {
    title: 'Compliance',
    children: [
      {
        title: 'Cookies Consent',
        key: 'CookieConsentManager',
      },
    ],
  },
  {
    title: 'Authorization',
    children: [
      {
        title: 'Roles and Permissions',
        key: 'RolesManager',
      },
    ],
  },
  {
    title: 'API',
    children: [
      {
        title: 'Build API',
        key: 'ApiManager',
      },
    ],
  },
  {
    title: 'Deployment',
    children: [
      {
        title: 'Create Nginx Configuration',
        key: 'NginxConfigurationContainer',
      },
    ],
  },
  {
    title: 'Scheduler',
    children: [
      {
        title: 'Configure Scheduled Tasks',
        key: 'TaskSchedulerContainer',
      },
    ],
  },
  {
    title: 'Linters',
    children: [
      {
        title: 'Configure ESLint',
        key: 'ESLintManager',
      },
    ],
  },
  {
    title: 'Queues',
    children: [
      {
        title: 'Create Jobs',
        key: 'JobsContainer',
      },
      {
        title: 'Manage Horizon',
        key: 'HorizonManager',
      },
      {
        title: 'Configure Supervisor',
        key: 'SupervisorContainer',
      },
    ],
  },
  {
    title: 'Authentication',
    children: [
      {
        title: 'Configure Authentication',
        key: 'AuthenticationManager',
      },
    ],
  },
  {
    title: 'Assets',
    children: [
      {
        title: 'Laravel Mix Configuration',
        key: 'LaravelMixContainer',
      },
    ],
  },
];
