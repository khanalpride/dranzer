<?php

namespace App\Builders\Processors\Config;

use Closure;
use Illuminate\Support\Str;
use PhpParser\Node\Expr\FuncCall;
use PhpParser\Node\Scalar\String_;
use App\Builders\Processors\PHPBuilderProcessor;
use App\Builders\PHP\Laravel\Framework\Config\LoggingBuilder;

/**
 * Class LoggingProcessor
 * @package App\Builders\Processors\Config
 */
class LoggingProcessor extends PHPBuilderProcessor
{
    /**
     * @param $builder
     * @param Closure $next
     * @return bool
     */
    public function process($builder, Closure $next): bool
    {
        $this
            ->processDefaultLoggingChannel($builder)
            ->processChannelPaths($builder)
            ->processSlack($builder);

        $next($builder);

        return true;
    }

    /**
     * @param LoggingBuilder $builder
     * @return LoggingProcessor
     */
    private function processChannelPaths(LoggingBuilder $builder): LoggingProcessor
    {
        $singleChannelPath = $this->channelPathToNode($this->getChannelPath('single'));

        $builder->setChannelPath('single', $singleChannelPath);

        $dailyChannelPath = $this->channelPathToNode($this->getChannelPath('daily'));

        $builder->setChannelPath('daily', $dailyChannelPath);

        return $this;
    }

    /**
     * @param LoggingBuilder $builder
     * @return LoggingProcessor
     */
    private function processDefaultLoggingChannel(LoggingBuilder $builder): LoggingProcessor
    {
        $defaultLoggingChannel = app('mutations')->for('logging')['channel'];

        $builder->setDefaultLoggingChannel($defaultLoggingChannel);

        return $this;
    }

    /**
     * @param LoggingBuilder $builder
     * @return void
     */
    private function processSlack(LoggingBuilder $builder): void
    {
        $slackConfig = app('mutations')->for('logging')['channels']['slack'];

        $loggingLevel = $slackConfig['level'];
        $username = $slackConfig['username'];
        $emoji = $slackConfig['emoji'];

        $builder->setSlackLoggingLevel($loggingLevel)
            ->setSlackUsername($username)
            ->setSlackEmoji($emoji);

    }

    /**
     * @param $channel
     * @return mixed
     */
    private function getChannelPath($channel)
    {
        $loggingMutations = app('mutations')->for('logging');

        return $loggingMutations['channels'][$channel]['path'];
    }

    /**
     * @param $path
     * @return FuncCall|String_
     */
    private function channelPathToNode($path)
    {
        return Str::startsWith($path, 'storage/') ? $this->funcCall('storage_path', [
            $this->string($path)
        ]) : $this->string($path);
    }
}
