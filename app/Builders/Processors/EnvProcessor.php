<?php

namespace App\Builders\Processors;

use Closure;
use App\Models\Project;
use App\Helpers\RegexHelpers;
use App\Builders\PHP\Laravel\Framework\EnvBuilder;
use App\Builders\Helpers\Mutations\DatabaseMutationHelpers;

/**
 * Class EnvProcessor
 * @package App\Builders\Processors\App
 */
class EnvProcessor extends CustomFileBuilderProcessor
{
    /**
     * @param $builder
     * @param Closure $next
     * @return bool
     */
    public function process($builder, Closure $next): bool
    {
        $this
            ->processAppVariables($builder)
            ->processBrandingVariables($builder)
            ->processExceptionVariables($builder)
            ->processDatabaseVariables($builder)
            ->processNotificationVariables($builder)
            ->processMailVariables($builder)
            ->processLoggingVariables($builder);

        $next($builder);

        return true;
    }

    /**
     * @param EnvBuilder $builder
     * @return EnvProcessor
     */
    private function processAppVariables(EnvBuilder $builder): EnvProcessor
    {
        $project = Project::auth()->uuid(app('project-id'))->first();

        if ($project) {
            /** @noinspection HttpUrlsUsage */
            $builder->setValue('APP_URL', 'http://' . $project->name . '.test');
        }

        return $this;
    }

    /**
     * @param EnvBuilder $builder
     * @return EnvProcessor
     */
    private function processBrandingVariables(EnvBuilder $builder): EnvProcessor
    {
        $branding = app('mutations')->for('branding');

        $builder->setValue('APP_NAME', $branding['name']);
        $builder->setValue('APP_DESCRIPTION', $branding['desc']);

        return $this;
    }

    /**
     * @param EnvBuilder $builder
     * @return EnvProcessor
     */
    private function processDatabaseVariables(EnvBuilder $builder): EnvProcessor
    {
        $dbConfig = app('mutations')->for('database');

        $dbConnection = $dbConfig['connection'];

        $connectionName = DatabaseMutationHelpers::GetConnectionName($dbConnection);

        $builder->setValue('DB_CONNECTION', $connectionName);

        $host = $dbConfig['host'];
        $port = $dbConfig['port'];
        $username = $dbConfig['username'];
        $db = $dbConfig['database'];
        $path = $dbConfig['url'];
        $dbAuthDatabase = $dbConfig['dbAuthDatabase'];

        if ($path && $dbConnection === 'sqlite') {
            $dbPath = rtrim($path, '\\/');
            $db = $db ? $dbPath . '/' . $db : $dbPath;
        }

        $builder->setValue('DB_HOST', $host);
        $builder->setValue('DB_PORT', $port);
        $builder->setValue('DB_DATABASE', $db);
        $builder->setValue('DB_USERNAME', $username);

        if ($dbConnection === 'mongodb') {
            $builder->setValue('DB_AUTHENTICATION_DATABASE', $dbAuthDatabase);
        } else {
            $builder->removeValue('DB_AUTHENTICATION_DATABASE');
        }

        return $this;
    }

    /**
     * @param EnvBuilder $builder
     * @return EnvProcessor
     */
    private function processExceptionVariables(EnvBuilder $builder): EnvProcessor
    {
        $exceptionsConfig = app('mutations')->for('exceptions');
        $sentryConfig = $exceptionsConfig['sentry'];

        if ($sentryConfig['enabled']) {
            $sentryDSN = $sentryConfig['dsn'];

            $sentryDSN = RegexHelpers::matches('/http[s]?:\/\/\w+@.*?\/\w+/', $sentryDSN) ? $sentryDSN : null;

            $builder
                ->newLine()
                ->setValue('SENTRY_LARAVEL_DSN', $sentryDSN);
        }

        return $this;
    }

    /**
     * @param EnvBuilder $builder
     * @return void
     */
    private function processLoggingVariables(EnvBuilder $builder): void
    {
        $defaultLoggingChannel = app('mutations')->for('logging')['channel'];

        $builder->setValue('LOG_CHANNEL', $defaultLoggingChannel);

        if ($defaultLoggingChannel === 'slack') {
            $slackLoggingWebhook = app('mutations')->for('logging')['channels']['slack']['webhookURL'];
            $builder
                ->newLine()
                ->setValue('LOG_SLACK_WEBHOOK_URL', $slackLoggingWebhook);
        }

    }

    /**
     * @param EnvBuilder $builder
     * @return EnvProcessor
     */
    private function processMailVariables(EnvBuilder $builder): EnvProcessor
    {
        $mailDriver = app('mutations')->for('mail')['driver'];

        $builder->setValue('MAIL_MAILER', $mailDriver);

        if ($mailDriver === 'smtp') {
            $smtpSettings = app('mutations')->for('mail')['config']['smtp'];
            $host = $smtpSettings['host'] ?? 'smtp.mailtrap.io';
            $port = $smtpSettings['port'] ?? 2525;
            $username = $smtpSettings['username'] ?? 'f9fde34ea51935';
            $password = $smtpSettings['password'] ?? 'be1268ec37ad28';
            $tls = $smtpSettings['tls'] ?? null;

            $builder->setValue('MAIL_HOST', $host);
            $builder->setValue('MAIL_PORT', $port);
            $builder->setValue('MAIL_USERNAME', $username);
            $builder->setValue('MAIL_PASSWORD', $password);
            $builder->setValue('MAIL_ENCRYPTION', $tls ? 'tls' : 'null');
        }

        $senderInfo = app('mutations')->for('mail')['config']['sender'];

        $senderName = $senderInfo['senderName'] ?? '${APP_NAME}';
        $senderEmail = $senderInfo['senderEmail'] ?? 'sender@example.com';

        $builder->setValue('MAIL_FROM_NAME', $senderName);
        $builder->setValue('MAIL_FROM_ADDRESS', $senderEmail);

        return $this;
    }

    /**
     * @param EnvBuilder $builder
     * @return EnvProcessor
     */
    private function processNotificationVariables(EnvBuilder $builder): EnvProcessor
    {
        $notificationMutations = app('mutations')->for('notifications');
        $notifications = collect($notificationMutations['notifications']);

        $addNexmoKeys = $notifications->first(fn ($n) => $n['via']['sms']['enabled'] ?? false) !== null;

        if ($addNexmoKeys) {
            $builder->newLine()
                ->setValue('NEXMO_KEY', '')
                ->setValue('NEXMO_SECRET', '');
        }

        return $this;
    }
}
