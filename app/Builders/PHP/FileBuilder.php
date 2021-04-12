<?php

namespace App\Builders\PHP;

use RuntimeException;
use PhpParser\Node\Expr;
use PhpParser\BuilderFactory;
use PhpParser\Node\Expr\Assign;
use PhpParser\Node\Stmt\Return_;
use PhpParser\Builder\Namespace_;
use PhpParser\Node\Expr\MethodCall;
use Illuminate\Support\Facades\File;
use App\Builders\Helpers\SortingHelpers;
use App\Builders\Contracts\IFileBuilder;
use App\Builders\PHP\Helpers\BuilderHelpers;
use App\Builders\PHP\Parser\Printers\Extensions\Standard;

/**
 * Class FileBuilder
 * @package App\Builders\PHP
 */
abstract class FileBuilder implements IFileBuilder
{
    use BuilderHelpers;

    /**
     * @var array
     */
    protected array $processors = [];
    /**
     * @var string
     */
    protected string $filename;
    /**
     * @var string
     */
    protected string $outputDir;
    /**
     * @var BuilderFactory
     */
    private BuilderFactory $factory;
    /**
     * @var Namespace_
     */
    private Namespace_ $namespace;
    /**
     * @var array
     */
    private array $methods = [];
    /**
     * @var array
     */
    private array $useStmts = [];
    /**
     * @var array
     */
    private array $stmts = [];
    /**
     * @var bool
     */
    private bool $canBuild = true;

    /**
     * FileBuilder constructor.
     */
    public function __construct()
    {
        $this->factory = new BuilderFactory();

        $this->namespace = $this->factory->namespace('NS');
    }

    /**
     * @return $this
     */
    public function prepare(): FileBuilder
    {
        return $this;
    }

    /**
     * @param $use
     * @param null $as
     * @return FileBuilder
     */
    public function use($use, $as = null): FileBuilder
    {
        $this->useStmts[] = [
            'stmt' => $use,
            'as'   => $as
        ];

        return $this;
    }

    /**
     * @param $var
     * @param string $methodName
     * @param array $args
     * @return MethodCall
     */
    protected function addMethodCall(
        $var,
        string $methodName,
        array $args = []
    ): MethodCall
    {
        if (is_string($var)) {
            $var = $this->var($var);
        }

        $methodCall = new MethodCall($var, $methodName, $args);
        $this->stmt($methodCall);

        return $methodCall;
    }

    /**
     * @return string
     */
    public function getFileName(): string
    {
        return $this->filename;
    }

    /**
     * @param string $filename
     * @return FileBuilder
     */
    public function setFilename(string $filename): FileBuilder
    {
        $this->filename = $filename;
        return $this;
    }

    /**
     * @return array
     */
    public function getNamespaceStatements(): array
    {
        return $this->namespace->getNode()->stmts ?? [];
    }

    /**
     * @return string
     */
    public function getContents(): string
    {
        $uniqueSortStmts = SortingHelpers::sortUseStmts($this->useStmts);

        foreach ($uniqueSortStmts as $uniqueSortStmt) {
            $stmt = $uniqueSortStmt['stmt'];
            $as = $uniqueSortStmt['as'] ?? null;

            if (!$as) {
                $this->namespace->addStmt($this->factory->use($stmt));
            } else {
                $this->namespace->addStmt($this->factory->use($stmt)->as($as));
            }
        }

        $stmts = collect($this->stmts)
            ->map(static function ($stmt) {
                // Separate the return statement from the
                // imports when generating a plain php file.
                if ($stmt instanceof Return_) {
                    $stmt->setAttribute('prependNewline', true);
                }

                return $stmt;
            })
            ->toArray();

        foreach ($stmts as $stmt) {
            $this->namespace->addStmt($stmt);
        }

        $nsNode = $this->namespace->getNode();

        /** @noinspection PhpPossiblePolymorphicInvocationInspection */
        if (!$nsNode || !count($nsNode->stmts)) {
            return "<?php" . PHP_EOL;
        }

        $prettyPrinter = new Standard(['shortArraySyntax' => true]);

        $code = $prettyPrinter->prettyPrintFile([$nsNode]);

        return str_replace("namespace NS;" . PHP_EOL . PHP_EOL, '', $code);
    }

    /**
     * @return bool
     */
    public function canBuild(): bool
    {
        return $this->canBuild;
    }

    /**
     * @return string
     */
    public function getOutputDir(): string
    {
        return $this->outputDir;
    }

    /**
     * @param string $outputDir
     * @return FileBuilder
     */
    public function setOutputDir(string $outputDir): FileBuilder
    {
        $this->outputDir = $outputDir;

        return $this;
    }

    /**
     * @return array
     */
    public function getProcessors(): array
    {
        return $this->processors;
    }

    /**
     * @param bool $canBuild
     * @return FileBuilder
     */
    public function setCanBuild(bool $canBuild): FileBuilder
    {
        $this->canBuild = $canBuild;
        return $this;
    }

    /**
     * @param $stmt
     * @return $this
     */
    protected function stmt($stmt): FileBuilder
    {
        $this->stmts[] = $stmt;

        return $this;
    }

    /**
     * @param $var
     * @param string $methodName
     * @param array $args
     * @return MethodCall
     */
    protected function inlineMethodCall(
        $var,
        string $methodName,
        array $args = []
    ): MethodCall
    {
        if (is_string($var)) {
            $var = $this->var($var);
        }

        return new MethodCall($var, $methodName, $args);
    }

    /**
     * @param $variable
     * @param $expr
     * @return Assign
     */
    protected function assign($variable, $expr): Assign
    {
        return $this->inlineAssign($variable, $expr);
    }

    /**
     * @param null $path
     * @return bool
     */
    protected function toDisk($path = null): bool
    {
        $contents = $this->getContents();

        if (trim($contents) === '') {
            throw new RuntimeException('File cannot be empty!');
        }

        return File::put(
                $path ?? $this->outputDir . '/' . $this->filename,
                $contents
            ) !== null;
    }

    /**
     * @param Expr $stmt
     * @param array $attributes
     * @return Return_
     */
    protected function ret(Expr $stmt, array $attributes = []): Return_
    {
        $return = $this->return($stmt, $attributes);

        $this->stmt($return);

        return $return;
    }

    /**
     * @param array $items
     * @param array $attributes
     * @return Return_
     */
    protected function retArr(array $items, array $attributes = []): Return_
    {
        $return = $this->return($this->arr($items), $attributes);

        $this->stmt($return);

        return $return;
    }
}
