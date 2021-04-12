<?php

/** @noinspection SpellCheckingInspection */

namespace App\Builders\PHP\Laravel\Framework\Config;

use PhpParser\Node\Expr;
use Monolog\Handler\NullHandler;
use App\Builders\PHP\FileBuilder;
use PhpParser\Node\Expr\ArrayItem;
use Monolog\Handler\StreamHandler;
use Monolog\Handler\SyslogUdpHandler;
use App\Builders\Helpers\MutationHelpers;
use App\Builders\Processors\Config\LoggingProcessor;

/**
 * Class LoggingBuilder
 * @package App\Builders\PHP\Laravel\Framework\Config
 */
class LoggingBuilder extends FileBuilder
{
    /**
     * @var array|string[]
     */
    protected array $processors = [
        LoggingProcessor::class,
    ];
    /**
     * @var string
     */
    protected string $filename = 'logging.php';

    /**
     * @var array
     */
    private array $mutations = [];
    /**
     * @var string
     */
    private string $defaultLoggingChannel = 'stack';
    /**
     * @var array
     */
    private array $channelPaths = [];
    /**
     * @var string
     */
    private string $slackLoggingLevel;
    /**
     * @var string
     */
    private string $slackUsername;
    /**
     * @var string
     */
    private string $slackEmoji;

    /**
     * @return LoggingBuilder
     */
    public function prepare(): LoggingBuilder
    {
        return $this->buildUseStatements();
    }

    /**
     * @return LoggingBuilder
     */
    private function buildUseStatements(): LoggingBuilder
    {
        $this->use(NullHandler::class);
        $this->use(StreamHandler::class);
        $this->use(SyslogUdpHandler::class);

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
     * @return LoggingBuilder
     */
    private function buildConfigArray(): LoggingBuilder
    {
        $this->retArr([
            $this->getDefaultKey(),
            $this->getChannelsKey()
        ]);
        return $this;
    }

    /**
     * @param string $defaultLoggingChannel
     * @return LoggingBuilder
     */
    public function setDefaultLoggingChannel(string $defaultLoggingChannel): LoggingBuilder
    {
        $this->defaultLoggingChannel = $defaultLoggingChannel;

        return $this;
    }

    /**
     * @return array
     */
    public function getChannelPaths(): array
    {
        return $this->channelPaths;
    }

    /**
     * @param array $channelPaths
     * @return LoggingBuilder
     */
    public function setChannelPaths(array $channelPaths): LoggingBuilder
    {
        $this->channelPaths = $channelPaths;

        return $this;
    }

    /**
     * @param string $channel
     * @return mixed
     */
    public function getChannelPath(string $channel): Expr
    {
        return $this->channelPaths[$channel] ?? $this->nopExpr();
    }

    /**
     * @param string $channel
     * @param $path
     * @return $this
     */
    public function setChannelPath(string $channel, $path): LoggingBuilder
    {
        $this->channelPaths[$channel] = is_string($path) ? $this->string($path) : $path;

        return $this;
    }

    /**
     * @param string $slackLoggingLevel
     * @return LoggingBuilder
     */
    public function setSlackLoggingLevel(string $slackLoggingLevel): LoggingBuilder
    {
        $this->slackLoggingLevel = $slackLoggingLevel;

        return $this;
    }

    /**
     * @param string $slackUsername
     * @return LoggingBuilder
     */
    public function setSlackUsername(string $slackUsername): LoggingBuilder
    {
        $this->slackUsername = $slackUsername;
        return $this;
    }

    /**
     * @param string $slackEmoji
     * @return LoggingBuilder
     */
    public function setSlackEmoji(string $slackEmoji): LoggingBuilder
    {
        $this->slackEmoji = $slackEmoji;
        return $this;
    }

    /**
     * @return ArrayItem
     */
    private function getDefaultKey(): ArrayItem
    {
        return $this->assoc('default', $this->envFuncCall('LOG_CHANNEL', [$this->string($this->defaultLoggingChannel)]), 'Default Log Channel', 'This option defines the default log channel that gets used when writing messages to the logs. The name specified in this option should match one of the channels defined in the "channels" configuration array.');
    }

    /**
     * @return ArrayItem
     */
    private function getChannelsKey(): ArrayItem
    {
        return $this->assoc('channels', $this->arr([
            $this->getStackConfigKey(),
            $this->getSingleConfigKey(),
            $this->getDailyConfigKey(),
            $this->getSlackConfigKey(),
            $this->getPaperTrailConfigKey(),
            $this->getStdErrConfigKey(),
            $this->getSysLogConfigKey(),
            $this->getErrorLogConfigKey(),
            $this->getNullLogConfigKey(),
            $this->getEmergencyLogConfigKey(),
        ]), 'Log Channels', 'Here you may configure the log channels for your application. Out of the box, Laravel uses the Monolog PHP logging library. This gives you a variety of powerful log handlers / formatters to utilize. Available Drivers: "single", "daily", "slack", "syslog", "errorlog", "monolog", "custom", "stack"');
    }

    /**
     * @param $channel
     * @return Expr
     */
    private function getPathNode($channel): Expr
    {
        return $this->getChannelPath($channel);
    }

    /**
     * @param $channel
     * @return string
     */
    private function getLoggingLevel($channel): string
    {
        return MutationHelpers::first("^logging\/channels\/$channel\/level$", $this->mutations) ?? 'info';
    }

    /**
     * @return ArrayItem
     */
    private function getStackConfigKey(): ArrayItem
    {
        $channels = MutationHelpers::first('logging\/channels\/config\/stack\/channels', $this->mutations) ?? ['single'];
        $channels = array_map(fn ($channel) => $this->string($channel), $channels);

        $options = MutationHelpers::first('logging\/channels\/config\/stack\/options', $this->mutations) ?? [];
        $ignoreExceptions = $options['ignoreExceptions'] ?? false;

        return $this->assoc('stack', $this->arr([
            $this->assoc('driver', 'stack'),
            $this->assoc('channels', $this->arr($channels)),
            $this->assoc('ignore_exceptions', $this->const($ignoreExceptions)),
        ]));
    }

    /**
     * @return ArrayItem
     */
    private function getSingleConfigKey(): ArrayItem
    {
        $path = $this->getPathNode('single');

        $loggingLevel = $this->getLoggingLevel('single');

        return $this->assoc('single', $this->arr([
            $this->assoc('driver', 'single'),
            $this->assoc('path', $path),
            $this->assoc('level', $loggingLevel)
        ]));
    }

    /**
     * @return ArrayItem
     */
    private function getDailyConfigKey(): ArrayItem
    {
        $path = $this->getPathNode('daily');

        $loggingLevel = $this->getLoggingLevel('daily');

        $days = MutationHelpers::first('logging\/channels\/daily\/days', $this->mutations) ?? 14;

        return $this->assoc('daily', $this->arr([
            $this->assoc('driver', 'daily'),
            $this->assoc('path', $path),
            $this->assoc('level', $loggingLevel),
            $this->assoc('days', $this->int($days)),
        ]));
    }

    /**
     * @return ArrayItem
     */
    private function getSlackConfigKey(): ArrayItem
    {
        return $this->assoc('slack', $this->arr([
            $this->assoc('driver', 'slack'),
            $this->assoc('url', $this->envFuncCall('LOG_SLACK_WEBHOOK_URL')),
            $this->assoc('username', $this->slackUsername),
            $this->assoc('emoji', $this->slackEmoji),
            $this->assoc('level', $this->slackLoggingLevel),
        ]));
    }

    /**
     * @return ArrayItem
     */
    private function getPaperTrailConfigKey(): ArrayItem
    {
        return $this->assoc('papertrail', $this->arr([
            $this->assoc('driver', 'monolog'),
            $this->assoc('debug', 'level'),
            $this->assoc('handler', $this->const('SyslogUdpHandler::class')),
            $this->assoc('handler_with', $this->arr([
                $this->assoc('host', $this->envFuncCall('PAPERTRAIL_URL')),
                $this->assoc('port', $this->envFuncCall('PAPERTRAIL_PORT')),
            ])),
        ]));
    }

    /**
     * @return ArrayItem
     */
    private function getStdErrConfigKey(): ArrayItem
    {
        return $this->assoc('stderr', $this->arr([
            $this->assoc('driver', 'monolog'),
            $this->assoc('handler', $this->const('StreamHandler::class')),
            $this->assoc('formatter', $this->envFuncCall('LOG_STDERR_FORMATTER')),
            $this->assoc('with', $this->arr([
                $this->assoc('stream', $this->string('php://stderr'))
            ])),
        ]));
    }

    /**
     * @return ArrayItem
     */
    private function getSysLogConfigKey(): ArrayItem
    {
        return $this->assoc('syslog', $this->arr([
            $this->assoc('driver', 'syslog'),
            $this->assoc('level', $this->getLoggingLevel('syslog')),
        ]));
    }

    /**
     * @return ArrayItem
     */
    private function getErrorLogConfigKey(): ArrayItem
    {
        return $this->assoc('errorlog', $this->arr([
            $this->assoc('driver', 'errorlog'),
            $this->assoc('level', $this->getLoggingLevel('errorlog')),
        ]));
    }

    /**
     * @return ArrayItem
     */
    private function getNullLogConfigKey(): ArrayItem
    {
        return $this->assoc('null', $this->arr([
            $this->assoc('driver', 'monolog'),
            $this->assoc('handler', $this->const('NullHandler::class')),
        ]));
    }

    /**
     * @return ArrayItem
     */
    private function getEmergencyLogConfigKey(): ArrayItem
    {
        return $this->assoc('emergency', $this->arr([
            $this->assoc('path', $this->funcCall('storage_path', [$this->string('logs/laravel.log')])),
        ]));
    }

}
