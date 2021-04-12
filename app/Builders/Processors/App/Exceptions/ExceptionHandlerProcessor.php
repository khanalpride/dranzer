<?php

namespace App\Builders\Processors\App\Exceptions;

use Closure;
use Throwable;
use Illuminate\Support\Facades\File;
use App\Builders\PHP\Laravel\ProjectBuilder;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Validation\ValidationException;
use App\Builders\Processors\PHPBuilderProcessor;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use App\Builders\PHP\Laravel\Framework\App\Exceptions\HandlerBuilder;
use App\Builders\PHP\Laravel\Framework\App\Http\Middleware\SentryContextMiddlewareBuilder;

/**
 * Class ExceptionHandlerProcessor
 * @package App\Builders\Processors\App\Exceptions
 */
class ExceptionHandlerProcessor extends PHPBuilderProcessor
{
    /**
     * @param $builder
     * @param Closure $next
     * @return bool
     */
    public function process($builder, Closure $next): bool
    {
        $this->processDoNotReportExceptions($builder)
            ->processSentry($builder)
            ->processErrorViews();

        $next($builder);

        return true;
    }

    /**
     * @param HandlerBuilder $builder
     * @return ExceptionHandlerProcessor
     */
    private function processDoNotReportExceptions(HandlerBuilder $builder): ExceptionHandlerProcessor
    {
        $exceptionsConfig = app('mutations')->for('exceptions');

        $doNotReport = $exceptionsConfig['doNotReport'];

        if ($doNotReport['authenticationException'] ?? false) {
            $builder
                ->use(AuthenticationException::class)
                ->dontReportException($this->const('AuthenticationException::class'));
        }

        if ($doNotReport['authorizationException'] ?? false) {
            $builder
                ->use(AuthorizationException::class)
                ->dontReportException($this->const('AuthorizationException::class'));
        }

        if ($doNotReport['httpException'] ?? false) {
            $builder
                ->use(HttpException::class)
                ->dontReportException($this->const('HttpException::class'));
        }

        if ($doNotReport['modelNotFoundException'] ?? false) {
            $builder
                ->use(ModelNotFoundException::class)
                ->dontReportException($this->const('ModelNotFoundException::class'));
        }

        if ($doNotReport['validationException'] ?? false) {
            $builder
                ->use(ValidationException::class)
                ->dontReportException($this->const('ValidationException::class'));
        }

        return $this;
    }

    /**
     * @return void
     */
    private function processErrorViews(): void
    {
        $basic = app('mutations')->for('exceptions')['basic'] ?? false;
        $messages = app('mutations')->for('exceptions')['messages'];

        if (!count($messages)) {
            return;
        }

        $sourceDir = app('static-assets') . '/resources/views/exceptions';
        $targetDir = app('project-dir') . '/resources/views/errors';

        File::ensureDirectoryExists($targetDir);

        foreach ($messages as $props) {
            $code = $props['code'];
            $title = $props['title'];
            $message = $props['message'];

            $pageContent = File::get("$sourceDir/$code.blade.php");

            if (!$basic) {
                $pageContent = str_replace('::minimal', '::illustrated-layout', $pageContent);
            }

            $pageContent = preg_replace("/(?<=title.......).*?(?=')/is", $title, $pageContent);
            $pageContent = preg_replace("/(?<=message.......).*?(?=')/is", $message, $pageContent);

            File::put("$targetDir/$code.blade.php", $pageContent);
        }

        if (!$basic) {
            File::copy("$sourceDir/illustrated-layout.blade.php", "$targetDir/illustrated-layout.blade.php");
        }

    }

    /**
     * @param HandlerBuilder $builder
     * @return ExceptionHandlerProcessor
     */
    private function processSentry(HandlerBuilder $builder): ExceptionHandlerProcessor
    {
        $exceptionMutations = app('mutations')->for('exceptions');

        $sentryMutations = $exceptionMutations['sentry'];

        $isSentryEnabled = $sentryMutations['enabled'] ?? false;

        if (!$isSentryEnabled) {
            return $this;
        }

        $reportable = $this->methodCallStmt(
            $this->methodCall(
                'this',
                'reportable',
                [
                    $this->staticClosure(
                        [
                            $this->param('throwable', Throwable::class),
                        ], [
                            $this->if(
                                $this->chainedFuncCalls(
                                    [
                                        $this->funcCall('app'),
                                        $this->funcCall(
                                            'bound', [
                                                $this->string('sentry'),
                                            ]
                                        ),
                                    ]
                                ),
                                [
                                    $this->chainedFuncCallStmt(
                                        $this->chainedFuncCalls(
                                            [
                                                $this->funcCall(
                                                    'app', [
                                                        $this->string('sentry'),
                                                    ]
                                                ),
                                                $this->funcCall(
                                                    'captureException', [
                                                        $this->var('throwable'),
                                                    ]
                                                ),
                                            ]
                                        )
                                    ),
                                ]
                            ),
                        ]
                    ),
                ]
            )
        );

        // By default, the register method is empty (contains a comment stmt)
        // so we use setStatements to remove previously added statements.
        $builder
            ->use('Throwable')
            ->getRegisterMethodBuilder()
            ->setStatements([$reportable]);

        // Create context middleware.
        $attachUserId = $sentryMutations['attachUserId'] ?? true;
        $attachUserEmail = $sentryMutations['attachUserEmail'] ?? true;

        if ($attachUserId || $attachUserEmail) {
            /**
             * As SentryContextMiddlewareBuilder is not automatically built,
             * we set the output directory and other properties manually.
             *
             * @more-info @method ProjectBuilder getBuilderMap
             */
            (new SentryContextMiddlewareBuilder)
                ->setOutputDir(app('project-dir') . '/app/Http/Middleware')
                ->setAttachUserId($attachUserId)
                ->setAttachUserEmail($attachUserEmail)
                ->prepare()
                ->build();
        }

        return $this;
    }
}
