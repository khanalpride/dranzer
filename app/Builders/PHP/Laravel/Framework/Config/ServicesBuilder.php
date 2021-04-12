<?php

namespace App\Builders\PHP\Laravel\Framework\Config;

use App\Builders\PHP\FileBuilder;
use PhpParser\Node\Expr\ArrayItem;

/**
 * Class ServicesBuilder
 * @package App\Builders\PHP\Laravel\Framework\Config
 */
class ServicesBuilder extends FileBuilder
{
    /**
     * @var string
     */
    protected string $filename = 'services.php';

    /**
     * @return ServicesBuilder
     */
    public function prepare(): ServicesBuilder
    {
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
     * @return ServicesBuilder
     */
    private function buildConfigArray(): ServicesBuilder
    {
        $this->retArr([
            $this->getMailGunKey(),
            $this->getPostMarkKey(),
            $this->getSESKey(),
        ]);

        return $this;
    }

    /**
     * @return ArrayItem
     */
    private function getMailGunKey(): ArrayItem
    {
        return $this->assoc('mailgun', $this->arr([
            $this->assoc('domain', $this->envFuncCall('MAILGUN_DOMAIN')),
            $this->assoc('secret', $this->envFuncCall('MAILGUN_SECRET')),
            $this->assoc('endpoint', $this->envFuncCall('MAILGUN_ENDPOINT', [$this->string('api.mailgun.net')])),
        ]), 'Third Party Services', 'This file is for storing the credentials for third party services such as Stripe, Mailgun, SparkPost and others. This file provides a sane default location for this type of information, allowing packages to have a conventional place to find your various credentials.');
    }

    /**
     * @return ArrayItem
     */
    private function getSESKey(): ArrayItem
    {
        return $this->assoc('ses', $this->arr([
            $this->assoc('key', $this->envFuncCall('SES_KEY')),
            $this->assoc('secret', $this->envFuncCall('SES_SECRET')),
            $this->assoc('region', $this->envFuncCall('SES_REGION', [$this->string('us-east-1')])),
        ]));
    }

    /**
     * @return ArrayItem
     */
    private function getPostMarkKey(): ArrayItem
    {
        return $this->assoc('postmark', $this->arr([
            $this->assoc('token', $this->envFuncCall('POSTMARK_TOKEN'))
        ]));
    }
}
