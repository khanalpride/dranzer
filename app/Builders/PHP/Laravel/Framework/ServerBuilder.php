<?php

/** @noinspection SpellCheckingInspection */

namespace App\Builders\PHP\Laravel\Framework;

use App\Builders\PHP\FileBuilder;

/**
 * Class ServerBuilder
 * @package App\Builders\PHP\Laravel\Framework
 */
class ServerBuilder extends FileBuilder
{
    /**
     * @var string
     */
    protected string $filename = 'server.php';

    /**
     * @return bool
     */
    public function build(): bool
    {
        return $this
            ->createIntroBlockComment()
            ->createURIVariable()
            ->emulateModRewrite()
            ->requireIndexPHPFile()
            ->toDisk();
    }

    /**
     * @return ServerBuilder
     */
    private function createIntroBlockComment(): ServerBuilder
    {
        $this->stmt(
            $this->blockComment(
                $this->doc('
            /**
             * Laravel - A PHP Framework For Web Artisans
             *
             * @package  Laravel
             * @author   Taylor Otwell <taylor@laravel.com>
             */
        '
                )
            )
        );
        return $this;
    }

    /**
     * @return ServerBuilder
     */
    private function createURIVariable(): ServerBuilder
    {
        $this->stmt(
            $this->assign('uri', $this->funcCall('urldecode', [
                $this->funcCall('parse_url', [
                    $this->arrayFetch('_SERVER', 'REQUEST_URI'),
                    $this->const('PHP_URL_PATH')
                ])
            ]))
        );
        return $this;
    }

    /**
     * @return ServerBuilder
     */
    private function requireIndexPHPFile(): ServerBuilder
    {
        $this->stmt($this->nop());

        $this->stmt(
            $this->requireOnce(
                $this->concat(
                    $this->const('__DIR__'),
                    $this->string('/public/index.php')
                )
            )
        );

        return $this;
    }

    /**
     * @return ServerBuilder
     */
    private function emulateModRewrite(): ServerBuilder
    {
        // When not using prettifier, make sure to align this block comment.
        $this->stmt(
            $this->blockComment(
                $this->doc('
            // This file allows us to emulate Apache\'s "mod_rewrite" functionality from the
            // built-in PHP web server. This provides a convenient way to test a Laravel
            // application without having installed a "real" web server software here.
            '
                ), ['appendNewLineAtEnd' => false])
        );
        $this->stmt(
            $this->if(
                $this->boolAnd(
                    $this->strictNotEquals(
                        $this->var('uri'), $this->string('/')
                    ), $this->funcCall('file_exists', [
                    $this->concat(
                        $this->concat($this->const('__DIR__'), $this->string('/public')),
                        $this->var('uri')
                    )
                ])), [
                $this->return($this->const('false'))
            ])
        );
        return $this;
    }
}
