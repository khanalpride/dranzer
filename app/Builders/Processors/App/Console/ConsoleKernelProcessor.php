<?php

namespace App\Builders\Processors\App\Console;

use Closure;
use App\Builders\Processors\PHPBuilderProcessor;
use App\Builders\PHP\Parser\Printers\Extensions\Standard;
use App\Builders\PHP\Laravel\Framework\App\Console\KernelBuilder;

/**
 * Class ConsoleKernelProcessor
 * @package App\Builders\Processors\App\Console
 */
class ConsoleKernelProcessor extends PHPBuilderProcessor
{
    /**
     * @param $builder
     * @param Closure $next
     * @return bool
     */
    public function process($builder, Closure $next): bool
    {
        $this->processScheduledTasks($builder);

        $next($builder);

        return true;
    }

    /**
     * @param KernelBuilder $builder
     */
    private function processScheduledTasks(KernelBuilder $builder): void
    {
        $tasks = app('mutations')->for('scheduler')['tasks'];

        $stmts = [];

        foreach ($tasks as $task) {
            $isShellCmd = $task['shell'] ?? false;

            $shellCmd = $task['shellCmd'] ?? null;

            if ($shellCmd) {
                /**
                 * Make the shell command a one-liner.
                 * For example:
                 *
                 * cd /var/www/html/laravel &&
                 * php artisan key:generate
                 *
                 * becomes
                 *
                 * cd /var/www/html/laravel && php artisan key:generate
                 */
                $commands = explode(
                    PHP_EOL,
                    $shellCmd
                );

                $shellCmd = implode(
                    ' ',
                    array_map(
                        static fn ($cmd) => trim(
                            str_ireplace(
                                PHP_EOL,
                                ' ',
                                $cmd
                            )
                        ),
                        $commands
                    )
                );
            }

            $frequencies = collect($task['frequencies'] ?? [])
                ->map(static fn ($f) => $f['value'])
                ->toArray();

            // Gotta have at-least one frequency for the
            // command to be scheduled.
            if (!count($frequencies)) {
                continue;
            }

            $commandSignature = $task['signature'] ?? '';

            $param = $isShellCmd ? $shellCmd : $commandSignature;

            // Create the parent call stmt.
            $execCommand = $this->methodCall(
                'schedule',
                $isShellCmd ? 'exec' : 'command',
                [$this->string($param)]
            );

            $stmt = $execCommand;

            // Chain the frequency calls.
            foreach ($frequencies as $frequency) {
                $stmt = $this->methodCall(
                    $stmt,
                    $frequency
                );
            }

            /**
             * Add the resulting expression as a statement.
             *
             * @more-info @method Standard pStmt_MethodCall
             */
            $stmts[] = $this->methodCallStmt($stmt);
        }

        $builder
            ->getScheduleMethodBuilder()
            ->addStatements($stmts);

    }

}
