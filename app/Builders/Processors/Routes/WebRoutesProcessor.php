<?php

namespace App\Builders\Processors\Routes;

use Closure;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use App\Builders\Helpers\BlueprintHelpers;
use App\Builders\Processors\PHPBuilderProcessor;
use App\Builders\PHP\Parser\Printers\Extensions\Standard;
use App\Builders\PHP\Laravel\Framework\Routes\WebRoutesBuilder;

/**
 * Class WebRoutesProcessor
 * @package App\Builders\Processors\Routes
 */
class WebRoutesProcessor extends PHPBuilderProcessor
{
    /**
     * @param $builder
     * @param Closure $next
     * @return bool
     */
    public function process($builder, Closure $next): bool
    {
        $this
            ->addWelcomeRoute($builder)
            ->processAuthRoutes($builder)
            ->processControllerRoutes($builder)
            ->processMailables($builder)
            ->processDecomposer($builder);

        $next($builder);

        return true;
    }

    /**
     * @param WebRoutesBuilder $builder
     * @return WebRoutesProcessor
     */
    private function processAuthRoutes(WebRoutesBuilder $builder): WebRoutesProcessor
    {
        $authMutations = app('mutations')->for('auth');

        $authEnabled = $authMutations['config']['enabled'];

        if (!$authEnabled) {
            return $this;
        }

        $authModule = $authMutations['module'];

        if ($authModule === 'ui') {
            $uiConfig = $authMutations['ui'];

            $allowResets = $uiConfig['resets'];
            $verifyEmails = $uiConfig['verify'];
            $allowRegistration = $uiConfig['registration'];

            $except = [];

            if (!$allowRegistration) {
                $except[] = $this->assoc('register', $this->const(false));
            }

            if (!$allowResets) {
                $except[] = $this->assoc('reset', $this->const(false));
            }

            $except[] = $this->assoc('verify', $this->const($verifyEmails ? 'true' : 'false'));

            $builder->use(Auth::class);

            $builder->addStatement($this->nop());

            $builder->addStatement(
                $this->staticCall('Auth', 'routes', [
                    $this->arr($except)
                ])
            );
            $builder->addStatement(
                $this->staticCall('Route', 'get', [
                    $this->string('/home'),
                    $this->arr([
                        $this->const('\App\Http\Controllers\HomeController::class'),
                        $this->string('index'),
                    ])
                ])
            );
        }

        if ($authModule === 'breeze') {
            $builder->addStatement($this->nop());

            $builder->addStatement(
                $this->chainableStaticCall('Route', 'get', [
                    $this->string('/dashboard'),
                    $this->staticClosure([], [
                        $this->return(
                            $this->funcCall('view', [$this->string('dashboard')])
                        )
                    ])
                ], [
                    $this->chainableMethodCall('middleware', [$this->arr([$this->string('auth')])]),
                    $this->chainableMethodCall('name', [$this->string('dashboard')])
                ])
            );

            $builder->addInclude(
                $this->requireOnce(
                    $this->concat(
                        $this->const('__DIR__'),
                        $this->string('/auth.php')
                    )
                )
            );
        }

        return $this;
    }

    /**
     * @param WebRoutesBuilder $builder
     * @return WebRoutesProcessor
     */
    private function processControllerRoutes(WebRoutesBuilder $builder): WebRoutesProcessor
    {
        $controllers = collect(app('mutations')->for('controllers')['controllers'])->filter(fn ($c) => $c['createRouteGroup'] && !$c['preset']);

        if (!count($controllers)) {
            return $this;
        }

        foreach ($controllers as $controller) {
            $rawControllerName = $controller['name'] ?? null;
            $controllerName = $controller['name'] ?? null;
            $methods = $controller['selectedMethods'] ?? [];
            $type = $controller['type'] ?? 'web';

            if (!$controllerName || $controllerName === '') {
                continue;
            }

            $controllerBaseNS = $type === 'web' ? 'App\Http\Controllers' : 'App\Http\Controllers\API';

            $builder->use("$controllerBaseNS\\$rawControllerName");

            $controllerRoute = str_ireplace("controller", '', $rawControllerName);

            $controllerRoute = Str::snake($controllerRoute);

            $isResourceController = $controller['isRC'] ?? false;

            $isSingleActionController = $controller['isSAC'] ?? false;

            $controllerRoutes = [];

            $resourceMethods = [
                'store',
                'update',
                'destroy',
                'index',
                'create',
                'edit',
                'show'
            ];

            if ($isSingleActionController) {
                $controllerRoutes[] = $this->staticCallStmt($this->staticCall('Route', 'get', [
                    $this->string('/'),
                    $this->arr([
                        $this->const("$rawControllerName::class"),
                        $this->string('__invoke')
                    ])
                ]));
            }

            $views = collect(app('mutations')->for('frontend')['views'])
                ->filter(fn ($v) => $v['controller'] && $v['controller'] === ($controller['id'] ?? null));

            foreach ($views as $view) {
                $viewName = $view['name'] ?? null;
                $uri = $view['uri'] ?? '/' . $viewName;

                $routeName = str_replace([
                    '-',
                    '_'
                ], '.', Str::snake($viewName));

                $methodName = Str::studly($viewName);

                $methodName = "show$methodName";

                $controllerRoutes[] = $this->chainableStaticCallStmt(
                    $this->chainableStaticCall('Route', 'get', [
                        $this->string($uri),
                        $this->arr([
                            $this->const("$rawControllerName::class"),
                            $this->string($methodName)
                        ])
                    ], [
                        $this->chainableMethodCall('name', [$this->string("show.$routeName")])
                    ])
                );
            }

            if (count($controllerRoutes)) {
                $builder->addStatement(
                    $this->staticCall('Route', 'group', [
                        $this->arr([
                            $this->assoc('prefix', $this->string($controllerRoute))
                        ]),
                        $this->staticClosure([], $controllerRoutes)
                    ])
                );
            }

            if ($isResourceController) {
                $except = collect(array_diff($resourceMethods, $methods))->map(fn ($e) => $this->string($e))->toArray();

                $builder->addStatement(
                    count($except) ?
                        $this->chainableStaticCall('Route', 'resource', [
                            $this->string($controllerRoute),
                            $this->const("$rawControllerName::class")
                        ], [
                            $this->chainableMethodCall(
                                'except', $except
                            )
                        ]) : $this->staticCall('Route', 'resource', [
                        $this->string($controllerRoute),
                        $this->const("$rawControllerName::class")
                    ]),
                );
            }
        }

        return $this;
    }

    /**
     * @param WebRoutesBuilder $builder
     * @return void
     */
    private function processDecomposer(WebRoutesBuilder $builder): void
    {
        $installDecomposer = app('mutations')->for('devTools')['installDecomposer'];

        if ($installDecomposer) {
            /** @noinspection SpellCheckingInspection */
            $builder->addGetRoute('decomposer', ['\Lubusin\Decomposer\Controllers\DecomposerController::class', 'index']);
        }

    }

    /**
     * @param WebRoutesBuilder $builder
     * @return WebRoutesProcessor
     */
    private function processMailables(WebRoutesBuilder $builder): WebRoutesProcessor
    {
        $mailables = app('mutations')->for('mail')['mailables'];

        if (!count($mailables)) {
            return $this;
        }

        $builder->use(Mail::class);

        $blueprints = app('mutations')->for('database')['blueprints'];

        $mailableRouteNodes = [];

        $createTestRouteGroup = collect($mailables)->first(fn ($m) => $m['testRoute'] ?? true) !== null;

        foreach ($mailables as $mailable) {
            $mailableName = $mailable['name'];

            $createTestRoute = $mailable['testRoute'] ?? true;

            if (!$createTestRoute) {
                continue;
            }

            $builder->use("App\Mail\\$mailableName");

            $typeHinted = $mailable['typeHint'] ?? [];

            $hintedModels = BlueprintHelpers::resolveTypeHintedModels($typeHinted, $blueprints);

            $mailableObjArgs = [];

            foreach ($hintedModels as $hintedModel) {
                $hintedModelName = $hintedModel['model'];

                $builder->use("App\Models\\$hintedModelName");

                $mailableObjArgs[] = $this->staticCall($hintedModelName, 'first');
            }

            $mailableObjInstantiationStmt = $this->new_($mailableName, $mailableObjArgs);

            $mailableRouteNodes[] = $this->staticCallStmt($this->staticCall('Route', 'get', [
                $this->string('/' . str_replace('_', '/', Str::snake($mailableName))),
                $this->staticClosure([], [
                    count($typeHinted)
                        ?
                        $this->blockComment(
                            $this->doc('/** @noinspection PhpUndefinedMethodInspection */'), ['appendNewLineAtEnd' => false]
                        )
                        : $this->nopExpr(),
                    $this->return($mailableObjInstantiationStmt),
                    $this->nopExpr(),
                    $this->comment(
                        (new Standard(['shortArraySyntax' => true]))->prettyPrint([
                            $this->return(
                                $this->chainableStaticCall('Mail', 'to', [
                                    $this->string('example@example.com')
                                ], [
                                    $this->chainableMethodCall('send', [
                                        $this->new_($mailableName, $mailableObjArgs)
                                    ])
                                ])
                            )
                        ])
                    )
                ])
            ]));

            $mailableRouteNodes[] = $this->nopExpr();
        }

        if ($createTestRouteGroup) {
            $builder->addStatement($this->staticCall('Route', 'group', [
                $this->arr([
                    $this->assoc('prefix', 'mailables')
                ]),
                $this->staticClosure([], $mailableRouteNodes)
            ]));
        }

        return $this;
    }

    /**
     * @param WebRoutesBuilder $builder
     * @return WebRoutesProcessor
     */
    private function addWelcomeRoute(WebRoutesBuilder $builder): WebRoutesProcessor
    {
        $builder->addStatement(
            $this->chainableStaticCallStmt(
                $this->chainableStaticCall('Route', 'get', [
                    $this->string('/'),
                    $this->staticClosure([], [
                        $this->return($this->funcCall('view', [$this->string('welcome')]))
                    ])
                ], [
                    $this->chainableMethodCall('name', [$this->string('home')])
                ])
            )
        );

        return $this;
    }
}
