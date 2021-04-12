<?php

namespace App\Builders\PHP\Laravel\Framework\App\Console\Commands;

use App\Builders\PHP\ClassBuilder;
use App\Builders\Contracts\IFileBuilder;
use App\Builders\PHP\Laravel\ArtisanCommands;
use App\Builders\PHP\Laravel\Parsers\Mutations\MutationsParser;
use App\Builders\PHP\MethodBuilder;
use App\Builders\PHP\Parser\Extensions\Stmt\FuncCall;
use App\Builders\PHP\Parser\Extensions\Stmt\MethodCall;
use App\Builders\PHP\PropertyBuilder;
use Illuminate\Console\Command;
use App\Builders\Processors\App\Console\Commands\AppInstallCommandProcessor;

/**
 * Class AppInstallCommandBuilder
 * @package App\Builders\PHP\Laravel\Framework\App\Console\Commands
 */
class AppInstallCommandBuilder extends ClassBuilder
{
    protected array $processors = [
        AppInstallCommandProcessor::class,
    ];

    /**
     * @var string
     */
    protected string $filename = 'AppInstallCommand.php';
    /**
     * @var string
     */
    protected string $namespace = 'App\Console\Commands';

    /**
     * @var array|string[]
     */
    protected array $classDefinition = [
        'name' => 'AppInstallCommand',
        'extend' => 'Command'
    ];

    /**
     * @var MethodBuilder
     */
    private MethodBuilder $handleMethodBuilder;

    /**
     * @var PropertyBuilder
     */
    private PropertyBuilder $signaturePropertyBuilder;

    public function prepare(): AppInstallCommandBuilder
    {
        return $this
            ->instantiateMethodBuilders()
            ->instantiatePropertyBuilders()
            ->buildUseStatements();
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
     * @return AppInstallCommandBuilder
     */
    protected function buildClass(): AppInstallCommandBuilder
    {
        $this->getSignaturePropertyBuilder()
            ->makeProtected()
            ->setValue(
                $this->string('app:install')
            );

        $this->addHandleMethodBuilder();
        $this->addSignaturePropertyBuilder();

        return $this;
    }

    /**
     * @return void
     */
    private function addHandleMethodBuilder(): void
    {
        $this->addMethodBuilder($this->handleMethodBuilder);

    }

    /**
     * @return void
     */
    private function addSignaturePropertyBuilder(): void
    {
        $this->addPropertyBuilder($this->signaturePropertyBuilder);

    }

    /**
     * @return $this
     */
    private function buildUseStatements(): AppInstallCommandBuilder
    {
        $this->useConsole();

        return $this;
    }

    /**
     * @return void
     */
    private function useConsole(): void
    {
        $this->use(Command::class);

    }

    /**
     * @return MethodBuilder
     */
    public function getHandleMethodBuilder(): MethodBuilder
    {
        return $this->handleMethodBuilder;
    }

    /**
     * @return PropertyBuilder
     */
    public function getSignaturePropertyBuilder(): PropertyBuilder
    {
        return $this->signaturePropertyBuilder;
    }

    /**
     * @return $this
     */
    private function instantiateMethodBuilders(): AppInstallCommandBuilder
    {
        $this->handleMethodBuilder = $this->getNewMethodBuilder('handle');
        return $this;
    }

    /**
     * @return $this
     */
    private function instantiatePropertyBuilders(): AppInstallCommandBuilder
    {
        $this->signaturePropertyBuilder = $this->getNewPropertyBuilder('signature');
        return $this;
    }
}
