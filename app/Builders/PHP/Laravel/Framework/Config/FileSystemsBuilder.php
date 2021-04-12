<?php

namespace App\Builders\PHP\Laravel\Framework\Config;

use App\Builders\PHP\FileBuilder;
use PhpParser\Node\Expr\ArrayItem;

/**
 * Class FileSystemsBuilder
 * @package App\Builders\PHP\Laravel\Framework\Config
 */
class FileSystemsBuilder extends FileBuilder
{
    /**
     * @var string
     */
    protected string $filename = 'filesystems.php';

    /**
     * @return $this
     */
    public function prepare(): FileSystemsBuilder
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
     * @return FileSystemsBuilder
     */
    private function buildConfigArray(): FileSystemsBuilder
    {
        $this->retArr([
            $this->getDefaultKey(),
            $this->getCloudKey(),
            $this->getDisksKey(),
            $this->getLinksKey()
        ]);

        return $this;
    }

    /**
     * @return ArrayItem
     */
    private function getDefaultKey(): ArrayItem
    {
        return $this->assoc('default', $this->envFuncCall('FILESYSTEM_DRIVER', [$this->string('local')]), 'Default Filesystem Disk', 'Here you may specify the default filesystem disk that should be used by the framework. The "local" disk, as well as a variety of cloud based disks are available to your application. Just store away!');
    }

    /**
     * @return ArrayItem
     */
    private function getCloudKey(): ArrayItem
    {
        return $this->assoc('cloud', $this->envFuncCall('FILESYSTEM_CLOUD', [$this->string('s3')]), 'Default Cloud Filesystem Disk', 'Many applications store files both locally and in the cloud. For this reason, you may specify a default "cloud" driver here. This driver will be bound as the Cloud disk implementation in the container.');
    }

    /**
     * @return ArrayItem
     */
    private function getDisksKey(): ArrayItem
    {
        return $this->assoc('disks', $this->arr([
            $this->getLocalConfigKey(),
            $this->getPublicConfigKey(),
            $this->getS3ConfigKey(),
        ]), 'Filesystem Disks', 'Here you may configure as many filesystem "disks" as you wish, and you may even configure multiple disks of the same driver. Defaults have been setup for each driver as an example of the required options. Supported Drivers: "local", "ftp", "sftp", "s3", "rackspace"');
    }

    /**
     * @return ArrayItem
     */
    private function getLocalConfigKey(): ArrayItem
    {
        return $this->assoc('local', $this->arr([
            $this->assoc('driver', 'local'),
            $this->assoc('root', $this->funcCall('storage_path', [
                $this->string('app')
            ])),
        ]));
    }

    /**
     * @return ArrayItem
     */
    private function getPublicConfigKey(): ArrayItem
    {
        return $this->assoc('public', $this->arr([
            $this->assoc('driver', 'local'),
            $this->assoc('root', $this->funcCall('storage_path', [
                $this->string('app/public')
            ])),
            $this->assoc('url', $this->concat(
                $this->envFuncCall('APP_URL'), $this->string('/storage')
            )),
            $this->assoc('visibility', 'public')
        ]));
    }

    /**
     * @return ArrayItem
     */
    private function getS3ConfigKey(): ArrayItem
    {
        return $this->assoc('s3', $this->arr([
            $this->assoc('driver', 's3'),
            $this->assoc('key', $this->envFuncCall('AWS_ACCESS_KEY_ID')),
            $this->assoc('secret', $this->envFuncCall('AWS_SECRET_ACCESS_KEY')),
            $this->assoc('region', $this->envFuncCall('AWS_DEFAULT_REGION')),
            $this->assoc('bucket', $this->envFuncCall('AWS_BUCKET')),
            $this->assoc('url', $this->envFuncCall('AWS_URL')),
            $this->assoc('endpoint', $this->envFuncCall('AWS_ENDPOINT')),
        ]));
    }

    /**
     * @return ArrayItem
     */
    private function getLinksKey(): ArrayItem
    {
        return $this->assoc('links', $this->arr([
            $this->assoc(
                $this->funcCall('public_path', [
                    $this->string('storage')
                ]),
                $this->funcCall('storage_path', [
                    $this->string('app/public')
                ])
            )
        ]), 'Symbolic Links', 'Here you may configure the symbolic links that will be created when the `storage:link` Artisan command is executed. The array keys should be the locations of the links and the values should be their targets.');
    }

}
