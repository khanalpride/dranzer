<?php

namespace App\Builders\Processors;

use Closure;

/**
 * Class NginxProcessor
 * @package App\Builders\Processors
 */
class NginxProcessor extends CustomFileBuilderProcessor
{
    /**
     * @param $builder
     * @param Closure $next
     * @return bool
     */
    public function process($builder, Closure $next): bool
    {
        $nginxConfig = app('mutations')->for('deployment')['nginxConfig'];

        if ($nginxConfig['copyConfig']) {
            $serverNames = collect($nginxConfig['serverNames'])->map(fn ($s) => $s['name'] ?? 'example.com')->toArray();

            $config = $this->getConfig(
                $nginxConfig['listeningPort'],
                $nginxConfig['root'],
                $serverNames,
                $nginxConfig['phpFPMVersion'],
                $nginxConfig['maxBodySize'],
            );

            $builder->setConfig(trim($config));
        } else {
            $builder->setCanBuild(false);
        }

        $next($builder);

        return true;
    }

    /**
     * @param $listeningPort
     * @param $root
     * @param $serverNames
     * @param $phpFPMVersion
     * @param $maxBodySize
     * @return string
     */
    private function getConfig($listeningPort, $root, $serverNames, $phpFPMVersion, $maxBodySize): string
    {
        $serverNames = implode(', ', $serverNames);
        return "
server {
    listen $listeningPort default_server;

    server_name $serverNames;

    root $root;

    client_max_body_size ${maxBodySize}M;

    index index.php;

    charset utf-8;

    location / {
        try_files \$uri \$uri/ /index.php?\$query_string;
    }

    location = /favicon.ico { access_log off; log_not_found off; }
    location = /robots.txt  { access_log off; log_not_found off; }

    error_page 404 /index.php;

    location ~ \.php$ {
        include snippets/fastcgi-php.conf;
        fastcgi_pass unix:/var/run/php/$phpFPMVersion.sock;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }
}
        ";
    }
}
