<?php

/** @noinspection SpellCheckingInspection */

namespace App\Builders\PHP\Laravel\Framework\Public_;

use Illuminate\Http\Request;
use App\Builders\PHP\FileBuilder;
use Illuminate\Contracts\Http\Kernel;

/**
 * Class IndexBuilder
 * @package App\Builders\PHP\Laravel\Framework\Public_
 */
class IndexBuilder extends FileBuilder
{
    /**
     * @var string
     */
    protected string $filename = 'index.php';

    /**
     * @return IndexBuilder
     */
    public function prepare(): IndexBuilder
    {
        return $this
            ->useRequest()
            ->useKernel();
    }

    /**
     * @return IndexBuilder
     */
    private function useKernel(): IndexBuilder
    {
        $this->use(Kernel::class);

        return $this;
    }

    /**
     * @return IndexBuilder
     */
    private function useRequest(): IndexBuilder
    {
        $this->use(Request::class);

        return $this;
    }

    /**
     * @return bool
     */
    public function build(): bool
    {
        return $this
            ->createIntroBlockComment()
            ->defineLaravelStartConstant()
            ->createMaintenanceModeBlockComment()
            ->requireMaintenanceScript()
            ->createAutoLoaderBlockComment()
            ->requireAutoLoader()
            ->createTurnOnLightsBlockComment()
            ->requireApp()
            ->createRunApplicationBlockComment()
            ->makeKernel()
            ->handleResponse()
            ->terminateKernel()
            ->toDisk();
    }

    /**
     * @return IndexBuilder
     */
    private function createAutoLoaderBlockComment(): IndexBuilder
    {
        $this->stmt($this->blockComment($this->doc('
            /*
            |--------------------------------------------------------------------------
            | Register The Auto Loader
            |--------------------------------------------------------------------------
            |
            | Composer provides a convenient, automatically generated class loader for
            | our application. We just need to utilize it! We\'ll simply require it
            | into the script here so that we don\'t have to worry about manual
            | loading any of our classes later on. It feels great to relax.
            |
            */
        ')));
        return $this;
    }

    /**
     * @return IndexBuilder
     */
    private function createIntroBlockComment(): IndexBuilder
    {
        $this->stmt($this->blockComment($this->doc('
            /**
             * Laravel - A PHP Framework For Web Artisans
             *
             * @package  Laravel
             * @author   Taylor Otwell <taylor@laravel.com>
             */
        ')));
        return $this;
    }

    /**
     * @return IndexBuilder
     */
    private function createMaintenanceModeBlockComment(): IndexBuilder
    {
        $this->stmt($this->blockComment($this->doc('
            /*
            |--------------------------------------------------------------------------
            | Check If Application Is Under Maintenance
            |--------------------------------------------------------------------------
            |
            | If the application is maintenance / demo mode via the "down" command we
            | will require this file so that any pre-rendered template can be shown
            | instead of starting the framework, which could cause an exception.
            |
            */
        ')));
        return $this;
    }

    /**
     * @return IndexBuilder
     */
    private function createRunApplicationBlockComment(): IndexBuilder
    {
        $this->stmt($this->blockComment($this->doc('
            /*
            |--------------------------------------------------------------------------
            | Run The Application
            |--------------------------------------------------------------------------
            |
            | Once we have the application, we can handle the incoming request
            | through the kernel, and send the associated response back to
            | the client\'s browser allowing them to enjoy the creative
            | and wonderful application we have prepared for them.
            |
            */
        ')));
        return $this;
    }

    /**
     * @return IndexBuilder
     */
    private function createTurnOnLightsBlockComment(): IndexBuilder
    {
        $this->stmt($this->blockComment($this->doc('
            /*
            |--------------------------------------------------------------------------
            | Turn On The Lights
            |--------------------------------------------------------------------------
            |
            | We need to illuminate PHP development, so let us turn on the lights.
            | This bootstraps the framework and gets it ready for use, then it
            | will load up this application so that we can run it and send
            | the responses back to the browser and delight our users.
            |
            */
        ')));
        return $this;
    }

    /**
     * @return IndexBuilder
     */
    private function terminateKernel(): IndexBuilder
    {
        $this->addMethodCall('kernel', 'terminate', [
            $this->var('request'),
            $this->var('response'),
        ]);
        return $this;
    }

    /**
     * @return IndexBuilder
     */
    private function handleResponse(): IndexBuilder
    {
        $this->stmt(
            $this->assign('response', $this->methodCall(
                $this->funcCall('tap', [
                    $this->methodCall('kernel', 'handle', [
                        $this->inlineAssign('request', $this->staticCall('Request', 'capture'))
                    ])
                ]),
                'send'
            ))
        );

        return $this;
    }

    /**
     * @return IndexBuilder
     */
    private function makeKernel(): IndexBuilder
    {
        $this->stmt(
            $this->assign('kernel', $this->inlineMethodCall('app', 'make', [
                $this->const('Kernel::class')
            ]))
        );
        return $this;
    }

    /**
     * @return IndexBuilder
     */
    private function requireApp(): IndexBuilder
    {
        $this->stmt(
            $this->assign(
                'app',
                $this->require($this->concat($this->const('__DIR__'), $this->string('/../bootstrap/app.php')))
            )
        );
        return $this;
    }

    /**
     * @return IndexBuilder
     */
    private function requireMaintenanceScript(): IndexBuilder
    {
        $this->stmt(
            $this->if(
                $this->funcCall('file_exists', [
                    $this->concat(
                        $this->const('__DIR__'),
                        $this->string('/../storage/framework/maintenance.php')
                    )
                ]), [
                    $this->require($this->concat($this->const('__DIR__'), $this->string('/../storage/framework/maintenance.php')))
                ]
            )
        );
        return $this;
    }

    /**
     * @return IndexBuilder
     */
    private function requireAutoLoader(): IndexBuilder
    {
        $this->stmt(
            $this->require($this->concat($this->const('__DIR__'), $this->string('/../vendor/autoload.php')))
        );
        return $this;
    }

    /**
     * @return IndexBuilder
     */
    private function defineLaravelStartConstant(): IndexBuilder
    {
        $this->stmt(
            $this->funcCall('define', [
                $this->string('LARAVEL_START'),
                $this->funcCall('microtime', [
                    $this->const('true')
                ])
            ])
        );
        return $this;
    }

}
