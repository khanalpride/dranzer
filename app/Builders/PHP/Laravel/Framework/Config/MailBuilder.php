<?php

namespace App\Builders\PHP\Laravel\Framework\Config;

use App\Builders\PHP\FileBuilder;
use PhpParser\Node\Expr\ArrayItem;

/**
 * Class MailBuilder
 * @package App\Builders\PHP\Laravel\Framework\Config
 */
class MailBuilder extends FileBuilder
{
    /**
     * @var string
     */
    protected string $filename = 'mail.php';

    /**
     * @return $this
     */
    public function prepare(): MailBuilder
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
     * @return MailBuilder
     */
    private function buildConfigArray(): MailBuilder
    {
        $this->retArr([
            $this->getDefaultKey(),
            $this->getMailersKey(),
            $this->getFromKey(),
            $this->getMarkDownKey(),
        ]);

        return $this;
    }

    /**
     * @return ArrayItem
     */
    private function getDefaultKey(): ArrayItem
    {
        return $this->assoc('default', $this->envFuncCall('MAIL_MAILER', [$this->string('smtp')]), 'Default Mailer', 'This option controls the default mailer that is used to send any email messages sent by your application. Alternative mailers may be setup and used as needed; however, this mailer will be used by default.');
    }

    /**
     * @return ArrayItem
     */
    private function getMailersKey(): ArrayItem
    {
        return $this->assoc('mailers', $this->arr([
            $this->assoc('smtp', $this->arr([
                $this->assoc('transport', 'smtp'),
                $this->assoc('host', $this->envFuncCall('MAIL_HOST', [$this->string('smtp.mailgun.org')])),
                $this->assoc('port', $this->envFuncCall('MAIL_PORT', [$this->int(587)])),
                $this->assoc('encryption', $this->envFuncCall('MAIL_ENCRYPTION', [$this->string('tls')])),
                $this->assoc('username', $this->envFuncCall('MAIL_USERNAME')),
                $this->assoc('password', $this->envFuncCall('MAIL_PASSWORD')),
                $this->assoc('timeout', $this->const('null')),
                $this->assoc('auth_mode', $this->const('null')),
            ])),

            $this->assoc('ses', $this->arr([
                $this->assoc('transport', 'ses'),
            ])),

            $this->assoc('mailgun', $this->arr([
                $this->assoc('transport', 'mailgun'),
            ])),

            $this->assoc('postmark', $this->arr([
                $this->assoc('transport', 'postmark'),
            ])),

            $this->assoc('sendmail', $this->arr([
                $this->assoc('transport', 'sendmail'),
                $this->assoc('path', '/usr/sbin/sendmail -bs'),
            ])),

            $this->assoc('log', $this->arr([
                $this->assoc('transport', 'log'),
                $this->assoc('channel', $this->envFuncCall('MAIL_LOG_CHANNEL')),
            ])),

            $this->assoc('array', $this->arr([
                $this->assoc('transport', 'array'),
            ])),

        ]), 'Mailer Configurations', 'Here you may configure all of the mailers used by your application plus their respective settings. Several examples have been configured for you and you are free to add your own as your application requires. Laravel supports a variety of mail "transport" drivers to be used while sending an e-mail. You will specify which one you are using for your mailers below. You are free to add additional mailers as required. Supported: "smtp", "sendmail", "mailgun", "ses", "postmark", "log", "array".');
    }

    /**
     * @return ArrayItem
     */
    private function getFromKey(): ArrayItem
    {
        return $this->assoc('from', $this->arr([
            $this->assoc('address', $this->envFuncCall('MAIL_FROM_ADDRESS', [$this->string('hello@example.com')])),
            $this->assoc('name', $this->envFuncCall('MAIL_FROM_NAME', [$this->string('Example')])),
        ]), 'Global "From" Address', 'You may wish for all e-mails sent by your application to be sent from the same address. Here, you may specify a name and address that is used globally for all e-mails that are sent by your application.');
    }

    /**
     * @return ArrayItem
     */
    private function getMarkDownKey(): ArrayItem
    {
        return $this->assoc('markdown', $this->arr([
            $this->assoc('theme', 'default'),
            $this->assoc('paths', $this->arr([
                $this->funcCall('resource_path', [$this->string('views/vendor/mail')])
            ])),
        ]), 'Markdown Mail Settings', 'If you are using Markdown based email rendering, you may configure your theme and component paths here, allowing you to customize the design of the emails. Or, you may simply stick with the Laravel defaults!');
    }
}
