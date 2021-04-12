<?php

namespace App\Builders\Processors\App\Console\Commands;

use Closure;
use Illuminate\Support\Str;
use Illuminate\Console\Command;
use App\Builders\Processors\PHPBuilderProcessor;
use App\Builders\PHP\Laravel\Framework\App\Console\Commands\ConsoleCommandsBuilder;

/**
 * Class ConsoleCommandsProcessor
 * @package App\Builders\Processors\App\Console\Commands
 */
class ConsoleCommandsProcessor extends PHPBuilderProcessor
{
    /**
     * @param $builder
     * @param Closure $next
     * @return bool
     */
    public function process($builder, Closure $next): bool
    {
        $tasks = collect(app('mutations')->for('scheduler')['tasks'])->filter(fn ($task) => !$task['shell'])->toArray();

        foreach ($tasks as $task) {
            $this->buildCommand($builder, $task);
        }

        $next($builder);

        return true;
    }

    /**
     * @param ConsoleCommandsBuilder $builder
     * @param $task
     */
    private function buildCommand(ConsoleCommandsBuilder $builder, $task): void
    {
        $name = $task['name'];

        if (!Str::contains(strtolower($name), 'command')) {
            $name .= 'Command';
        }

        $signature = $task['signature'];
        $description = $task['description'] ?? 'Performs a long-running operation...';

        $signatureProperty = $builder->getNewPropertyBuilder('signature');
        $signatureProperty
            ->makeProtected()
            ->setValue($signature)
            ->getDocBuilder()
            ->addCommentLine('The name and signature of the console command.')
            ->addVar('string');

        $builder->addPropertyBuilder($signatureProperty);

        $descriptionProperty = $builder->getNewPropertyBuilder('description');
        $descriptionProperty
            ->makeProtected()
            ->setValue($description)
            ->getDocBuilder()
            ->addCommentLine('The console command description.')
            ->addVar('string');

        $builder->addPropertyBuilder($descriptionProperty);

        $handleMethodBuilder = $builder->getNewMethodBuilder('handle');
        $handleMethodBuilder
            ->setReturnType('int')
            ->addStatement($this->return($this->int(0)))
            ->getDocBuilder()
            ->addCommentLine('Execute the console command.')
            ->setReturnType('int');

        $builder->addMethodBuilder($handleMethodBuilder);

        $builder
            ->use(Command::class)
            ->setClassDefinition($name, 'App\Console\Commands', 'Command')
            ->updateClassDefinition()
            ->setFilename("$name.php")
            ->toDisk();

        $builder->reset();
    }
}
