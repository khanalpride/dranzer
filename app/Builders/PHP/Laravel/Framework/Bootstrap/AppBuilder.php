<?php

namespace App\Builders\PHP\Laravel\Framework\Bootstrap;

use App\Builders\PHP\FileBuilder;
use Illuminate\Foundation\Application;

/**
 * Class AppBuilder
 * @package App\Builders\PHP\Laravel\Framework\Bootstrap
 */
class AppBuilder extends FileBuilder
{
    /**
     * @var string
     */
    protected string $filename = 'app.php';

    /**
     * @return $this
     */
    public function prepare(): AppBuilder
    {
        return $this;
    }

    /**
     * @return bool
     */
    public function build(): bool
    {
        return $this
            ->createAppInstance()
            ->bindInterfaces()
            ->toDisk($this->outputDir . '/' . $this->filename);
    }

    /**
     * @return AppBuilder
     */
    private function createAppInstance(): AppBuilder
    {
        $this->stmt(
            $this->blockComment(
                $this->doc('
            /*
            |--------------------------------------------------------------------------
            | Create The Application
            |--------------------------------------------------------------------------
            |
            | The first thing we will do is create a new Laravel application instance
            | which serves as the "glue" for all the components of Laravel, and is
            | the IoC container for the system binding all of the various parts.
            |
            */
        ')
            )
        );

        $this->stmt($this->nop());

        $this->stmt(
            $this->assign('app', $this->new_(Application::class, [
                $this->coalesce(
                    $this->arrayFetch(
                        $this->var('ENV'),
                        $this->string('APP_BASE_PATH')
                    ),
                    $this->funcCall(
                        'dirname', [
                            $this->const('__DIR__')
                        ]
                    )
                )
            ]))
        );

        return $this;
    }

    /**
     * @return AppBuilder
     */
    private function bindInterfaces(): AppBuilder
    {
        $this->stmt($this->nop());

        $this->stmt(
            $this->blockComment(
                $this->doc('
                /*
                |--------------------------------------------------------------------------
                | Bind Important Interfaces
                |--------------------------------------------------------------------------
                |
                | Next, we need to bind some important interfaces into the container so
                | we will be able to resolve them when needed. The kernels serve the
                | incoming requests to this application from both the web and CLI.
                |
                */
                ')
            )
        );

        $this->stmt($this->nop());

        $this->addMethodCall(
            $this->var('app'), 'singleton', [
                $this->const('Illuminate\Contracts\Http\Kernel::class'),
                $this->const('App\Http\Kernel::class'),
            ]
        );

        $this->addMethodCall(
            $this->var('app'), 'singleton', [
                $this->const('Illuminate\Contracts\Console\Kernel::class'),
                $this->const('App\Console\Kernel::class'),
            ]
        );

        $this->addMethodCall(
            $this->var('app'), 'singleton', [
                $this->const('Illuminate\Contracts\Debug\ExceptionHandler::class'),
                $this->const('App\Exceptions\Handler::class'),
            ]
        );

        $this->stmt(
            $this->blockComment(
                $this->doc('
                /*
                |--------------------------------------------------------------------------
                | Return The Application
                |--------------------------------------------------------------------------
                |
                | This script returns the application instance. The instance is given to
                | the calling script so we can separate the building of the instances
                | from the actual running of the application and sending responses.
                |
                */
                ')
            )
        );

        $this->stmt($this->nop());

        $this->stmt(
            $this->return(
                $this->var('app')
            )
        );

        return $this;
    }
}
