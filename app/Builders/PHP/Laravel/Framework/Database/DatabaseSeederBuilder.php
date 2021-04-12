<?php

namespace App\Builders\PHP\Laravel\Framework\Database;

use Illuminate\Database\Seeder;
use App\Builders\PHP\ClassBuilder;
use App\Builders\PHP\MethodBuilder;

/**
 * Class DatabaseSeederBuilder
 * @package App\Builders\PHP\Laravel\Framework\Database
 */
class DatabaseSeederBuilder extends ClassBuilder
{
    /**
     * @var bool
     */
    public static bool $customBuilder = true;
    /**
     * @var string|null
     */
    protected string $namespace = 'Database\Seeders';
    /**
     * @var string|null
     */
    protected string $filename = 'DatabaseSeeder.php';
    /**
     * @var array|string[]
     */
    protected array $classDefinition = [
        'name'   => 'DatabaseSeeder',
        'extend' => 'Seeder',
    ];
    /**
     * @var
     */
    private $runMethod;
    /**
     * @var array
     */
    private $useStatements = [];
    /**
     * @var array
     */
    private $seeders = [];

    /**
     * @return $this
     */
    private function instantiateMethodBuilders(): DatabaseSeederBuilder
    {
        $this->runMethod = new MethodBuilder('run');
        return $this;
    }

    /**
     * @return $this
     */
    private function buildUseStatements(): DatabaseSeederBuilder
    {
        $this->use(Seeder::class);
        return $this;
    }

    /**
     * @return bool
     */
    public function build(): bool
    {
        return $this
            ->buildUseStatements()
            ->instantiateMethodBuilders()
            ->buildClass()
            ->toDisk();
    }

    /**
     * @return DatabaseSeederBuilder
     */
    protected function buildClass(): DatabaseSeederBuilder
    {
        $this->getRunMethod()
            ->addStatements($this->getRunMethodStatements())
            ->setReturnType('void')
            ->getDocBuilder()
            ->addCommentLine('Run the database seeds.')
            ->setReturnType('void');

        $this->addMethodBuilder($this->getRunMethod());

        return $this;
    }

    /**
     * @param $useStatement
     * @return $this
     */
    public function addUseStatement($useStatement): self
    {
        $this->useStatements[] = $useStatement;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getRunMethod(): MethodBuilder
    {
        return $this->runMethod;
    }

    /**
     * @return array
     */
    public function getUseStatements(): array
    {
        return $this->useStatements;
    }

    /**
     * @param $classDefinition
     * @return $this
     */
    public function overrideClassDefinition($classDefinition): DatabaseSeederBuilder
    {
        $this->classDefinition = $classDefinition;
        $this->updateClassDefinition();
        return $this;
    }

    /**
     * @return array
     */
    public function getSeeders(): array
    {
        return $this->seeders;
    }

    /**
     * @param array $seeders
     * @return DatabaseSeederBuilder
     */
    public function setSeeders(array $seeders): DatabaseSeederBuilder
    {
        $this->seeders = $seeders;
        return $this;
    }

    /**
     * @return array
     */
    private function getRunMethodStatements(): array
    {
        $statements = [];

        foreach ($this->getSeeders() as $seeder) {
//            $this->use($seeder);
            $statements[] = $this->const($seeder . '::class');
        }

        if (count($statements) < 1) {
            return [];
        }

        return [$this->methodCall('this', 'call', [count($statements) > 1 ? $this->arr($statements) : $statements[0]])];
    }
}
