<?php

namespace App\Builders\PHP\Laravel\Framework\Config;

use App\Builders\PHP\FileBuilder;
use PhpParser\Node\Expr\ArrayItem;

/**
 * Class HashingBuilder
 * @package App\Builders\PHP\Laravel\Framework\Config
 */
class HashingBuilder extends FileBuilder
{
    /**
     * @var string
     */
    protected string $filename = 'hashing.php';

    /**
     * @return $this
     */
    public function prepare(): HashingBuilder
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
     * @return HashingBuilder
     */
    private function buildConfigArray(): HashingBuilder
    {
        $this->retArr([
            $this->getDriverKey(),
            $this->getBCryptKey(),
            $this->getArgonKey()
        ]);

        return $this;
    }

    /**
     * @return ArrayItem
     */
    private function getDriverKey(): ArrayItem
    {
        return $this->assoc('driver', 'bcrypt', 'Default Hash Driver', 'This option controls the default hash driver that will be used to hash passwords for your application. By default, the bcrypt algorithm is used; however, you remain free to modify this option if you wish. Supported: "bcrypt", "argon", "argon2id"');
    }

    /**
     * @return ArrayItem
     */
    private function getBCryptKey(): ArrayItem
    {
        return $this->assoc('bcrypt', $this->arr([
            $this->assoc('rounds', $this->envFuncCall('BCRYPT_ROUNDS', [$this->int(10)]))
        ]), 'Bcrypt Options', 'Here you may specify the configuration options that should be used when passwords are hashed using the Bcrypt algorithm. This will allow you to control the amount of time it takes to hash the given password.');
    }

    /**
     * @return ArrayItem
     */
    private function getArgonKey(): ArrayItem
    {
        return $this->assoc('argon', $this->arr([
            $this->assoc('memory', $this->int(1024)),
            $this->assoc('threads', $this->int(2)),
            $this->assoc('time', $this->int(2)),
        ]), 'Argon Options', 'Here you may specify the configuration options that should be used when passwords are hashed using the Argon algorithm. These will allow you to control the amount of time it takes to hash the given password.');
    }
}
