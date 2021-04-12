<?php

namespace App\Builders\PHP\Laravel\Framework\Routes;

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

/**
 * Class ConsoleRoutesBuilder
 * @package App\Builders\PHP\Laravel\Framework\Routes
 */
class ConsoleRoutesBuilder extends RoutesBuilder
{
    /**
     * @var string
     */
    protected string $filename = 'console.php';

    /**
     * @return $this
     */
    public function prepare(): ConsoleRoutesBuilder
    {
        $this
            ->use(Inspiring::class)
            ->use(Artisan::class)
            ->addStatement(
                $this->chainableStaticCallStmt(
                    $this->chainableStaticCall('Artisan', 'command', [
                        $this->string('inspire'),
                        $this->closure([
                            $this->param('command', 'callable')
                        ], [
                            // Wrap the method call in methodCallStmt since
                            // we're inside the callable and want the call to
                            // terminate (with a semi-colon: see Extensions/Standard.php).
                            $this->methodCallStmt(
                                $this->methodCall('command', 'comment', [
                                    $this->staticCall('Inspiring', 'quote')
                                ])
                            )
                        ])
                    ], [
                        $this->chainableMethodCall('describe', [
                            $this->string('Display an inspiring quote')
                        ])
                    ])
                )
            );

        return $this;
    }
}
