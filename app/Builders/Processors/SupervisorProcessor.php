<?php

namespace App\Builders\Processors;

use Closure;

/**
 * Class SupervisorProcessor
 * @package App\Builders\Processors
 */
class SupervisorProcessor extends CustomFileBuilderProcessor
{
    /**
     * @param $builder
     * @param Closure $next
     * @return bool
     */
    public function process($builder, Closure $next): bool
    {
        $supervisorConfig = app('mutations')->for('queues')['supervisorConfig'];

        if ($supervisorConfig['copyConfig']) {
            $config = $this->getConfig($supervisorConfig['artisanPath'], $supervisorConfig['logFilePath'], $supervisorConfig['username']);
            $builder->setConfig(trim($config));
        } else {
            $builder->setCanBuild(false);
        }

        $next($builder);

        return true;
    }


    /**
     * @param $artisanPath
     * @param $logFilePath
     * @param $username
     *
     * @return string
     *
     * @noinspection SpellCheckingInspection
     */
    private function getConfig($artisanPath, $logFilePath, $username): string
    {
        return "
[program:horizon]
process_name=%(program_name)s
command=php $artisanPath horizon
autostart=true
autorestart=true
redirect_stderr=true
user=$username
stdout_logfile=$logFilePath
stopwaitsecs=3600
        ";
    }
}
