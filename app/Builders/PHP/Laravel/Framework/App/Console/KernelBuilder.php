<?php

/** @noinspection ClassConstantCanBeUsedInspection */

namespace App\Builders\PHP\Laravel\Framework\App\Console;

use App\Builders\PHP\ClassBuilder;
use App\Builders\PHP\MethodBuilder;
use App\Builders\PHP\PropertyBuilder;
use Illuminate\Foundation\Console\Kernel;
use Illuminate\Console\Scheduling\Schedule;
use App\Builders\Processors\App\Console\ConsoleKernelProcessor;

/**
 * Class KernelBuilder
 * @package App\Builders\PHP\Laravel\Framework\App\Console\KernelBuilder
 */
class KernelBuilder extends ClassBuilder
{
    /**
     * @var array|string[]
     */
    protected array $processors = [
        ConsoleKernelProcessor::class,
    ];
    /**
     * @var string
     */
    protected string $filename = 'Kernel.php';
    /**
     * @var string
     */
    protected string $namespace = 'App\Console';
    /**
     * @var array|string[]
     */
    protected array $classDefinition = [
        'name'   => 'Kernel',
        'extend' => 'ConsoleKernel',
    ];
    /**
     * @var string
     */
    private string $commandsDirectoryName = 'Commands';
    /**
     * @var string
     */
    private string $consoleRoutePath = 'routes/console.php';
    /**
     * @var MethodBuilder
     */
    private MethodBuilder $scheduleMethodBuilder;
    /**
     * @var MethodBuilder
     */
    private MethodBuilder $commandsMethodBuilder;
    /**
     * @var PropertyBuilder
     */
    private PropertyBuilder $commandsPropertyBuilder;
    /**
     * @var array
     */
    private array $commands = [];

    /**
     * @return KernelBuilder
     */
    public function prepare(): KernelBuilder
    {
        return $this
            ->instantiatePropertyBuilders()
            ->instantiateMethodBuilders()
            ->buildUseStatements()
            ->setDefaults();
    }

    /**
     * @return KernelBuilder
     */
    private function instantiateMethodBuilders(): KernelBuilder
    {
        // protected function schedule(...)
        $this->setScheduleMethodBuilder(new MethodBuilder('schedule'));
        // protected function commands(...)
        $this->setCommandsMethodBuilder(new MethodBuilder('commands'));
        return $this;
    }

    /**
     * @return KernelBuilder
     */
    private function instantiatePropertyBuilders(): KernelBuilder
    {
        $this->setCommandsPropertyBuilder(new PropertyBuilder('commands'));
        return $this;
    }

    /**
     * @return KernelBuilder
     */
    protected function buildUseStatements(): KernelBuilder
    {
        $this->useScheduleClass()->useConsoleKernel();
        return $this;
    }

    /**
     * @return KernelBuilder
     */
    private function setDefaults(): KernelBuilder
    {
        $this->getCommandsPropertyBuilder()
            ->setValue($this->getCommands())
            ->makeProtected()
            ->getDocBuilder()
            ->addCommentLine('The Artisan commands provided by your application.');

        $this->getScheduleMethodBuilder()
            ->makeProtected()
            ->addParameter($this->param('schedule', 'Schedule'))
            ->setComment("Define the application's command schedule.")
            ->getDocBuilder()
            ->addCommentLine("Define the application's command schedule.");

        $this->getCommandsMethodBuilder()->addStatement(
            $this->methodCall(
                'this', 'load', [
                    $this->concat($this->const('__DIR__'), $this->string('/' . $this->getCommandsDirectoryName())),
                ]
            )
        )
            ->addStatement($this->nop())
            ->addStatement(
                $this->require(
                    $this->funcCall(
                        'base_path', [
                            $this->string($this->getConsoleRoutePath()),
                        ]
                    ), $this->shouldSuppressPHPStormInspectionWarnings() ? '/** @noinspection PhpIncludeInspection */' : null
                )
            )
            ->makeProtected()
            ->getDocBuilder()
            ->addCommentLine('Register the commands for the application.');

        return $this;
    }

    /**
     * @return void
     */
    private function useConsoleKernel(): void
    {
        $this->use(Kernel::class, 'ConsoleKernel');
    }

    /**
     * @return KernelBuilder
     */
    private function useScheduleClass(): KernelBuilder
    {
        $this->use(Schedule::class);

        return $this;
    }

    /**
     * @return bool
     */
    public function build(): bool
    {
        return $this
            ->buildClass()
            ->toDisk();
    }

    /**
     * @return KernelBuilder
     */
    protected function buildClass(): KernelBuilder
    {
        return $this
            ->addCommandsProperty()
            ->addScheduledMethod()
            ->addCommandsMethod();
    }

    /**
     * @return KernelBuilder
     */
    private function addCommandsMethod(): KernelBuilder
    {
        $this->addMethodBuilder($this->getCommandsMethodBuilder());
        return $this;
    }

    /**
     * @return KernelBuilder
     */
    private function addCommandsProperty(): KernelBuilder
    {
        $this->addPropertyBuilder($this->getCommandsPropertyBuilder());
        return $this;
    }

    /**
     * @return KernelBuilder
     */
    private function addScheduledMethod(): KernelBuilder
    {
        $this->addMethodBuilder($this->getScheduleMethodBuilder());
        return $this;
    }

    /**
     * @return PropertyBuilder
     */
    public function getCommandsPropertyBuilder(): PropertyBuilder
    {
        return $this->commandsPropertyBuilder;
    }

    /**
     * @param PropertyBuilder $commandsPropertyBuilder
     * @return KernelBuilder
     */
    public function setCommandsPropertyBuilder(PropertyBuilder $commandsPropertyBuilder): KernelBuilder
    {
        $this->commandsPropertyBuilder = $commandsPropertyBuilder;
        return $this;
    }

    /**
     * @return array
     */
    public function getCommands(): array
    {
        return $this->commands;
    }

    /**
     * @return MethodBuilder
     */
    public function getScheduleMethodBuilder(): MethodBuilder
    {
        return $this->scheduleMethodBuilder;
    }

    /**
     * @return MethodBuilder
     */
    public function getCommandsMethodBuilder(): MethodBuilder
    {
        return $this->commandsMethodBuilder;
    }

    /**
     * @param MethodBuilder $commandsMethodBuilder
     * @return KernelBuilder
     */
    public function setCommandsMethodBuilder(MethodBuilder $commandsMethodBuilder): KernelBuilder
    {
        $this->commandsMethodBuilder = $commandsMethodBuilder;
        return $this;
    }

    /**
     * @return string
     */
    public function getCommandsDirectoryName(): string
    {
        return $this->commandsDirectoryName;
    }

    /**
     * @return string
     */
    public function getConsoleRoutePath(): string
    {
        return $this->consoleRoutePath;
    }

    /**
     * @param string $consoleRoutePath
     * @return KernelBuilder
     */
    public function setConsoleRoutePath(string $consoleRoutePath): KernelBuilder
    {
        $this->consoleRoutePath = $consoleRoutePath;
        return $this;
    }

    /**
     * @param MethodBuilder $scheduleMethodBuilder
     * @return void
     */
    private function setScheduleMethodBuilder(MethodBuilder $scheduleMethodBuilder): void
    {
        $this->scheduleMethodBuilder = $scheduleMethodBuilder;
    }
}
