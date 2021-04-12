<?php

namespace App\Builders\PHP\Laravel\Framework\Database\Seeders;

use Illuminate\Support\Str;
use Illuminate\Database\Seeder;
use App\Builders\PHP\ClassBuilder;
use App\Builders\PHP\MethodBuilder;

/**
 * Class SeederBuilder
 * @package App\Builders\PHP\Laravel\Framework\Database\Seeders
 */
class SeederBuilder extends ClassBuilder
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
     * @var
     */
    private $projectId;
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
    private $blueprints = [];
    /**
     * @var string
     */
    private string $tableName;
    /**
     * @var array
     */
    private array $columns;
    /**
     * @var int
     */
    private int $seedCount = 50;

    /**
     * @return SeederBuilder
     */
    private function instantiateMethodBuilders(): SeederBuilder
    {
        $this->runMethod = new MethodBuilder('run');
        return $this;
    }

    /**
     * @return SeederBuilder
     */
    private function buildUseStatements(): SeederBuilder
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
     * @return SeederBuilder
     */
    protected function buildClass(): SeederBuilder
    {
        $table = $this->getTableName();

        $modelName = Str::studly(Str::singular($table));

        $this->use('App\Models\\' . $modelName);

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
     *
     * @return SeederBuilder
     */
    public function addUseStatement($useStatement): SeederBuilder
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
     *
     * @return SeederBuilder
     */
    public function overrideClassDefinition($classDefinition): SeederBuilder
    {
        $this->classDefinition = $classDefinition;
        $this->updateClassDefinition();
        return $this;
    }

    /**
     * @return mixed
     */
    public function getProjectId()
    {
        return $this->projectId;
    }

    /**
     * @param mixed $projectId
     *
     * @return SeederBuilder
     */
    public function setProjectId($projectId): SeederBuilder
    {
        $this->projectId = $projectId;
        return $this;
    }

    /**
     * @return array
     *
     * @noinspection PhpUnused
     * @noinspection UnknownInspectionInspection
     */
    public function getBlueprints(): array
    {
        return $this->blueprints;
    }

    /**
     * @param array $blueprints
     * @return SeederBuilder
     */
    public function setBlueprints(array $blueprints): SeederBuilder
    {
        $this->blueprints = $blueprints;
        return $this;
    }

    /**
     * @return string
     *
     * @noinspection PhpUnused
     * @noinspection UnknownInspectionInspection
     */
    public function getTableName(): string
    {
        return $this->tableName;
    }

    /**
     * @param string $tableName
     * @return SeederBuilder
     */
    public function setTableName(string $tableName): SeederBuilder
    {
        $this->tableName = $tableName;

        return $this;
    }

    /**
     * @return array
     */
    public function getColumns(): array
    {
        return $this->columns;
    }

    /**
     * @param array $columns
     * @return SeederBuilder
     */
    public function setColumns(array $columns): SeederBuilder
    {
        $this->columns = $columns;

        return $this;
    }

    /**
     * @return int
     */
    public function getSeedCount(): int
    {
        return $this->seedCount;
    }

    /**
     * @param int $seedCount
     * @return SeederBuilder
     */
    public function setSeedCount(int $seedCount): SeederBuilder
    {
        $this->seedCount = $seedCount;

        return $this;
    }

    /**
     * @return array
     */
    private function getRunMethodStatements(): array
    {
        $statements = [];

        $table = $this->getTableName();
        $seedCount = $this->getSeedCount();

        $modelName = Str::studly(Str::singular($table));

        $statements[] = $this->chainedFuncCalls(
            [
                $this->staticCall($modelName, 'factory'),
                $this->funcCall('count', [$this->int($seedCount)]),
                $this->funcCall('create'),
            ]
        );

        return $statements;
    }
}
