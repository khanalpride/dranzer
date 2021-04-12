<?php

/** @noinspection SpellCheckingInspection */

namespace App\Builders\PHP\Laravel\Framework;

use Illuminate\Support\Str;
use App\Builders\PHP\FileBuilder;
use App\Builders\Processors\EnvProcessor;

/**
 * Class EnvBuilder
 * @package App\Builders\PHP\Laravel\Framework
 */
class EnvBuilder extends FileBuilder
{
    /**
     * @var string
     */
    protected string $filename = '.env';
    /**
     * @var array|string[]
     */
    protected array $processors = [
        EnvProcessor::class,
    ];
    /**
     * @var array
     */
    private $keyValueMap = [];

    /**
     * EnvBuilder constructor.
     */
    public function prepare(): EnvBuilder
    {
        return $this->setDefaults();
    }

    /**
     * @return EnvBuilder
     */
    private function setDefaults(): EnvBuilder
    {
        $this
            ->setValue('APP_NAME', 'Laravel')
            ->setValue('APP_DESCRIPTION', 'Built with Dranzer')
            ->setValue('APP_ENV', 'local')
            ->setValue('APP_KEY', 'base64:FvG8Ta/fAkfcJOJ4cxX5kLQEQnYaSpNqHIirxXLVqi0=')
            ->setValue('APP_DEBUG', 'true')
            ->setValue('APP_URL', 'http://localhost')
            ->newLine()
            ->setValue('LOG_CHANNEL', 'stack')
            ->newLine()
            ->setValue('DB_CONNECTION', 'mysql')
            ->setValue('DB_HOST', '127.0.0.1')
            ->setValue('DB_PORT', '3306')
            ->setValue('DB_DATABASE', 'homestead')
            ->setValue('DB_USERNAME', 'homestead')
            ->setValue('DB_PASSWORD', '')
            ->setValue('DB_AUTHENTICATION_DATABASE', 'admin')
            ->newLine()
            ->setValue('BROADCAST_DRIVER', 'log')
            ->setValue('CACHE_DRIVER', 'file')
            ->setValue('QUEUE_CONNECTION', 'redis')
            ->setValue('SESSION_DRIVER', 'file')
            ->setValue('SESSION_LIFETIME', 120)
            ->newLine()
            ->setValue('REDIS_HOST', '127.0.0.1')
            ->setValue('REDIS_PASSWORD', '')
            ->setValue('REDIS_PORT', 6379)
            ->newLine()
            ->setValue('MAIL_MAILER', 'smtp')
            ->setValue('MAIL_HOST', 'smtp.mailtrap.io')
            ->setValue('MAIL_PORT', '2525')
            ->setValue('MAIL_USERNAME', 'f9fde34ea51935')
            ->setValue('MAIL_PASSWORD', 'be1268ec37ad28')
            ->setValue('MAIL_ENCRYPTION', 'null')
            ->setValue('MAIL_FROM_NAME', '${APP_NAME}')
            ->setValue('MAIL_FROM_ADDRESS', 'sender@example.com')
            ->newLine()
            ->setValue('AWS_ACCESS_KEY_ID', '')
            ->setValue('AWS_SECRET_ACCESS_KEY', '')
            ->setValue('AWS_DEFAULT_REGION', 'us-east-1')
            ->setValue('AWS_BUCKET', '')
            ->newLine()
            ->setValue('PUSHER_APP_ID', null)
            ->setValue('PUSHER_APP_KEY', null)
            ->setValue('PUSHER_APP_SECRET', null)
            ->setValue('PUSHER_APP_CLUSTER', 'mt1')
            ->newLine()
            ->setValue('MIX_PUSHER_APP_KEY', '"${PUSHER_APP_KEY}"')
            ->setValue('MIX_PUSHER_APP_CLUSTER', '"${PUSHER_APP_CLUSTER}"')
            ->newLine()
            ->setValue('SCOUT_QUEUE', 'true')
            ->setValue('SCOUT_DRIVER', 'algolia')
            ->setValue('SCOUT_MAX_RESULTS', '3')
            ->newLine()
            ->setValue('ALGOLIA_APP_ID', '')
            ->setValue('ALGOLIA_SECRET', '');

        return $this;
    }

    /**
     * @return bool
     */
    public function build(): bool
    {
        return $this->toDisk();
    }

    /**
     * @param $comment
     * @return $this
     */
    public function addComment($comment): EnvBuilder
    {
        $this->setValue('~__COMMENT__~' . Str::random(9), $comment);
        return $this;
    }

    /**
     * @return $this
     */
    public function newLine(): EnvBuilder
    {
        $this->setValue('~__EMPTY__~' . Str::random(9), null);

        return $this;
    }

    /**
     * @param $key
     * @param $value
     * @return $this
     */
    public function setValue($key, $value): EnvBuilder
    {
        if (Str::contains($key, '$') || (Str::contains($value, ' ') && !Str::startsWith($key, '~__COMMENT__~'))) {
            $value = '"' . $value . '"';
        }

        $this->keyValueMap[$key] = $value;

        return $this;
    }

    /**
     * @param $key
     * @return EnvBuilder
     */
    public function removeValue($key): EnvBuilder
    {
        if (array_key_exists($key, $this->keyValueMap)) {
            unset($this->keyValueMap[$key]);
        }

        return $this;
    }

    /**
     * @param $key
     * @return mixed|null
     */
    public function getValue($key)
    {
        if ($this->hasKey($key)) {
            return $this->keyValueMap[$key];
        }

        return null;
    }

    /**
     * @param $key
     * @return bool
     */
    public function hasKey($key): bool
    {
        return array_key_exists($key, $this->keyValueMap);
    }

    /**
     * @return string
     */
    public function getContents(): string
    {
        $output = '';

        foreach ($this->keyValueMap as $key => $value) {
            if (Str::startsWith($key, '~__EMPTY__~')) {
                $output .= "\n";
            } else if (Str::startsWith($key, '~__COMMENT__~')) {
                $output .= "# $value\n";
            } else {
                $output .= "$key=$value\n";
            }
        }

        return preg_replace('/\n(^\n){2,}/i', '', $output);
    }
}
