<?php

namespace App\Builders\Processors\App\Console\Commands;

use Closure;
use App\Builders\PHP\Laravel\ArtisanCommands;
use App\Builders\Processors\PHPBuilderProcessor;
use App\Builders\PHP\Parser\Extensions\Stmt\FuncCall;
use App\Builders\PHP\Parser\Extensions\Stmt\MethodCall;
use App\Builders\PHP\Laravel\Framework\App\Console\Commands\AppInstallCommandBuilder;

/**
 * Class AppInstallCommandProcessor
 * @package App\Builders\Processors\App\Console\Commands
 */
class AppInstallCommandProcessor extends PHPBuilderProcessor
{
    /**
     * @param $builder
     * @param Closure $next
     * @return bool
     */
    public function process($builder, Closure $next): bool
    {
        $installAdmin = app('mutations')->for('ui')['installAdmin'];

        $this->processCommands($builder, $installAdmin);

        $next($builder);

        return true;
    }

    /**
     * @param AppInstallCommandBuilder $builder
     * @param bool $installAdmin
     */
    private function processCommands(AppInstallCommandBuilder $builder, bool $installAdmin): void
    {
        $commands = ArtisanCommands::getCommands();

        $statements = [];

        $statements[] = $this->inlineAssign(
            'env',
            $this->ternary(
                $this->strictEquals(
                    $this->funcCall('env', [
                        $this->string('APP_ENV'),
                        $this->string('local')
                    ]),
                    $this->string('local')
                ),
                $this->string('dev'),
                $this->string('prod'),
            )
        );

        $statements[] = $this->nop();

        if (count($commands)) {
            $statements[] = $this->nop();
        }

        // Dynamic Artisan Commands
        foreach ($commands as $command) {
            $parts = explode('--', $command);

            $cmd = trim($parts[0]);
            $params = count($parts) > 1 ? array_slice($parts, 1) : [];

            /** @noinspection SlowArrayOperationsInLoopInspection */
            $statements = array_merge($statements, $this->getArtisanCallStatements($cmd, $params));
            $statements[] = $this->nop();
        }

        $statements[] = $this->nop();

        $statements = array_merge($statements, $this->getArtisanCallStatements('migrate', [
            'seed',
            'force'
        ]));

        $statements[] = $this->nop();

        // Admin
        if ($installAdmin) {
            $statements = array_merge($statements, $this->getArtisanCallStatements('orchid:admin', [
                'name'     => 'admin',
                'email'    => 'admin@admin.com',
                'password' => 'password',
            ], 'orchid:admin admin admin@admin.com ********', true));
            $statements[] = $this->nop();

            $statements = array_merge($statements, $this->getArtisanCallStatements('admin:reset-permissions'));
            $statements[] = $this->nop();
        }

        $statements[] = $this->getOutputStatement([
            $this->concat($this->string('Executing yarn && yarn run '), $this->var('env'))
        ]);

        $statements[] = $this->getShellExecStatement([
            $this->concat($this->string('yarn && yarn run '), $this->var('env'))
        ]);

        $statements[] = $this->nop();

        $statements[] = $this->getOutputStatement([], 'newLine');

        $statements[] = $this->getOutputStatement([
            $this->string('Installation Complete!'),
        ], 'success');

        $builder
            ->getHandleMethodBuilder()
            ->addStatements($statements);
    }

    /**
     * @param $call
     * @param array $params
     * @param null $message
     * @param false $associativeParams
     * @return array
     */
    private function getArtisanCallStatements($call, $params = [], $message = null, $associativeParams = false): array
    {
        $statements = [];

        $paramsAsStr = implode(' ', collect($params)->map(fn ($p) => ' --' . $p)->toArray());

        $completeCall = $call . $paramsAsStr;

        $statements[] = $this->getOutputStatement([
            $this->string('Executing ' . ($message ?: $completeCall))
        ]);

        if (count($params)) {
            if (!$associativeParams) {
                $params = collect($params)->map(function ($p) {
                    $parts = explode('=', $p);
                    $parts[0] = '--' . $parts[0];
                    return count($parts) > 1
                        ? $this->assoc($this->string(trim($parts[0])), $this->string(str_ireplace('"', '', trim($parts[1]))))
                        : $this->assoc($this->string(trim($parts[0])), $this->const(true));
                })->toArray();
            } else {
                $params = collect($params)->map(function ($v, $k) {
                    return $this->assoc($this->string(trim($k)), $this->string(trim($v)));
                })->toArray();
            }

            $callStmt = $this->methodCall('this', 'call', [
                $this->string($call),
                $this->arr($params)
            ]);
        } else {
            $callStmt = $this->methodCall('this', 'call', [
                $this->string($call)
            ]);
        }

        $statements[] = $this->methodCallStmt($callStmt);

        return $statements;
    }


    /**
     * @param array $titleStatements
     * @param string $type
     * @return MethodCall
     */
    private function getOutputStatement(array $titleStatements, $type = 'title'): MethodCall
    {
        return $this->methodCallStmt(
            $this->methodCall(
                $this->methodCall('this', 'getOutput'),
                $type,
                $titleStatements
            )
        );
    }

    /**
     * @param $shellCommandStatements
     * @return FuncCall
     */
    private function getShellExecStatement($shellCommandStatements): FuncCall
    {
        return $this->funcCallStmt(
            $this->funcCall('echo', [
                $this->funcCall('shell_exec', $shellCommandStatements)
            ])
        );
    }
}
