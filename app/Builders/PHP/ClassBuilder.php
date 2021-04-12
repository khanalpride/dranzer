<?php

/** @noinspection SpellCheckingInspection */

namespace App\Builders\PHP;

use Closure;
use RuntimeException;
use Illuminate\Http\Request;
use PhpParser\Builder\Class_;
use PhpParser\BuilderFactory;
use PhpParser\Builder\Namespace_;
use PhpParser\Node\Stmt\ClassConst;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use App\Builders\Helpers\SortingHelpers;
use App\Builders\Contracts\IFileBuilder;
use App\Builders\PHP\Helpers\BuilderHelpers;
use App\Builders\PHP\Laravel\ProjectBuilder;
use App\Builders\PHP\Constants\PHPStormInspections;
use App\Builders\PHP\Parser\Printers\Extensions\Standard;

/**
 * Class ClassBuilder
 * @package App\Builders\PHP
 */
abstract class ClassBuilder implements IFileBuilder
{
    use BuilderHelpers;

    /**
     * Indicates that this builder should not be
     * included in the default build pipeline.
     *
     * @more-info @method ProjectBuilder getBuilderMap
     *
     * @var bool
     */
    public static bool $customBuilder = false;
    /**
     * @var array
     */
    protected array $processors = [];
    /**
     * The namespace of the target class.
     *
     * @var string $namespace
     */
    protected string $namespace = 'App';
    /**
     * This node is initialized by the BuilderFactory
     * and contains methods to build the class.
     *
     * @var Namespace_ $nsNode
     */
    protected Namespace_ $nsNode;
    /**
     * Indicates whether the class has a namespace or not.
     * This property is necessary as the namespace node provides
     * the methods required for building the class. If true,
     * the namespace is removed after the class code is generated.
     *
     * @var bool $removeNamespace
     */
    protected bool $removeNamespace = false;
    /**
     * Key-value map providing basic information to construct
     * the class.
     *
     * @key name
     * @key extends
     * @key namespace
     *
     * At-least the class name must be provided with the 'name' key.
     *
     * To override the class definition, override this array and call
     * the updateClassDefinition method. The override must occur before
     * any calls to namespace or class related methods (e.g. use, trait).
     *
     * @var array $classDefinition
     */
    protected array $classDefinition = [];
    /**
     * The absolute path of the builder.
     *
     * @var string $outputDir
     */
    protected string $outputDir;
    /**
     * The filename of the generated class.
     *
     * @var string $filename
     */
    protected string $filename;
    /**
     * @var bool
     */
    protected bool $suppressPHPStormInspectionWarnings = true;
    /**
     * @var DocBlockBuilder
     */
    protected DocBlockBuilder $docBuilder;
    /**
     * @var bool
     */
    private bool $canBuild = true;
    /**
     * @var BuilderFactory
     */
    private BuilderFactory $factory;
    /**
     * @var Class_
     */
    private Class_ $class;
    /**
     * @var array
     */
    private array $implements = [];
    /**
     * @var array
     */
    private array $traits = [];
    /**
     * @var array
     */
    private array $methods = [];
    /**
     * @var array
     */
    private array $methodBuilders = [];
    /**
     * @var array
     */
    private array $props = [];
    /**
     * @var array
     */
    private array $consts = [];
    /**
     * @var array
     */
    private array $useStmts = [];

    /**
     *
     * ClassBuilder constructor.
     */
    public function __construct()
    {
        $this->setFactory(new BuilderFactory())
            ->setDocBuilder(new DocBlockBuilder());

        $this->setNamespaceNode($this->getNamespace());

        $this->updateClassDefinition();
    }

    /**
     * @return $this
     */
    public function prepare(): ClassBuilder
    {
        return $this;
    }

    /**
     * @param string $use
     * @param string|null $as
     * @return ClassBuilder
     */
    public function use(string $use, $as = null): ClassBuilder
    {
        $this->useStmts[] = [
            'stmt' => $use,
            'as'   => $as
        ];

        return $this;
    }

    /**
     * @return ClassBuilder
     */
    protected function useClosure(): ClassBuilder
    {
        $this->use(Closure::class);

        return $this;
    }

    /**
     * @return ClassBuilder
     */
    protected function useIlluminateHttpRequest(): ClassBuilder
    {
        $this->use(Request::class);

        return $this;
    }

    /**
     * @return ClassBuilder
     */
    protected function useIlluminateRouteFacade(): ClassBuilder
    {
        $this->use(Route::class);
        return $this;
    }

    /**
     * @return ClassBuilder
     */
    protected function useIlluminateServiceProvider(): ClassBuilder
    {
        $this->use(ServiceProvider::class);
        return $this;
    }

    /**
     * @return bool
     */
    public function build(): bool
    {
        return true;
    }

    /**
     * @return BuilderFactory|null
     */
    protected function builder(): ?BuilderFactory
    {
        return $this->factory;
    }

    /**
     * @param ClassConstBuilder $classConstBuilder
     * @return ClassConst
     */
    protected function addClassConst(
        ClassConstBuilder $classConstBuilder
    ): ClassConst
    {
        $classConst = $classConstBuilder->getConcrete();
        $this->consts[] = $classConst;

        return $classConst;
    }

    /**
     * @param MethodBuilder $methodBuilder
     * @return ClassBuilder
     */
    public function addMethodBuilder(MethodBuilder $methodBuilder): ClassBuilder
    {
        foreach ($this->methodBuilders as $builder) {
            if ($builder->getMethodName() === $methodBuilder->getMethodName()) {
                return $this;
            }
        }

        // Suppress inspections (if enabled)
        if (
            $methodBuilder->getMethodName() !== '__construct' &&
            $methodBuilder->isInspectionSuppressionEnabled() &&
            app('code-gen')['suppressInspections'] && !$methodBuilder->getReturnType()
        ) {
            $suppressEAInspection = app('code-gen')['using-ea-inspections'] && !$methodBuilder->isInspectionSuppressed(PHPStormInspections::RETURN_TYPE_CAN_BE_DECLARED_INSPECTION);

            if ($suppressEAInspection) {
                $methodBuilder->getDocBuilder()->suppressInspection(
                    PHPStormInspections::RETURN_TYPE_CAN_BE_DECLARED_INSPECTION
                );
            }

            if (!$suppressEAInspection && !$methodBuilder->isInspectionSuppressed(PHPStormInspections::PHP_MISSING_RETURN_TYPE_INSPECTION)) {
                $methodBuilder->getDocBuilder()->suppressInspection(
                    PHPStormInspections::PHP_MISSING_RETURN_TYPE_INSPECTION
                );
            }
        }

        // If the method has a return type but the doc block has none, add it to the doc block.
        $shouldAddReturnTypeToDocBlock = $methodBuilder->getReturnType() && !$methodBuilder->getDocBuilder()->hasReturnType();

        if ($shouldAddReturnTypeToDocBlock) {
            $returnType = $methodBuilder->getReturnType();
            $methodBuilder
                ->getDocBuilder()
                ->setReturnType($returnType);
        }

        $this->methods[] = $methodBuilder->getConcrete();

        $this->methodBuilders[] = $methodBuilder;

        return $this;
    }

    /**
     * @noinspection PhpUnused
     * @noinspection UnknownInspectionInspection
     * @param array $methodBuilders
     * @return ClassBuilder
     */
    public function addMethodBuilders(array $methodBuilders): ClassBuilder
    {
        foreach ($methodBuilders as $methodBuilder) {
            $this->addMethodBuilder($methodBuilder);
        }
        return $this;
    }

    /**
     * @param PropertyBuilder $propertyBuilder
     * @return ClassBuilder
     */
    public function addPropertyBuilder(PropertyBuilder $propertyBuilder): ClassBuilder
    {
        $prop = $propertyBuilder->getConcrete();

        $this->props[] = $prop;

        return $this;
    }

    /**
     * @param $trait
     * @return ClassBuilder
     */
    public function addTrait($trait): ClassBuilder
    {
        $this->addTraits([$trait]);

        return $this;
    }

    /**
     * @param array $traits
     * @return ClassBuilder
     */
    public function addTraits(array $traits): ClassBuilder
    {
        if (!$this->getClass()) {
            throw new RuntimeException(
                'The class must be initialized before a trait can be added.'
            );
        }

        $traits = array_filter($traits, fn ($trait) => !$this->hasTrait($trait));

        $traits = array_merge($this->traits, $traits);

        if (!count($traits)) {
            return $this;
        }

        $this->traits = $traits;

        return $this;
    }

    /**
     * @return bool
     */
    public function canBuild(): bool
    {
        return $this->canBuild;
    }

    /**
     * @param bool $canBuild
     * @return ClassBuilder
     */
    public function setCanBuild(bool $canBuild): ClassBuilder
    {
        $this->canBuild = $canBuild;

        return $this;
    }

    /**
     * @param string $name
     * @return ClassBuilder
     */
    public function setNamespaceNode(string $name): ClassBuilder
    {
        $this->nsNode = $this->factory->namespace($name);
        return $this;
    }

    /**
     * @return string
     */
    public function getNamespace(): string
    {
        return $this->namespace;
    }

    /**
     * @return string
     */
    public function getOutputDir(): string
    {
        return $this->outputDir;
    }

    /**
     * @noinspection PhpUnused
     * @noinspection UnknownInspectionInspection
     * @param string $outputDir
     * @return ClassBuilder
     */
    public function setOutputDir(string $outputDir): ClassBuilder
    {
        $this->outputDir = $outputDir;

        return $this;
    }

    /**
     * @param BuilderFactory $factory
     * @return ClassBuilder
     */
    public function setFactory(BuilderFactory $factory): ClassBuilder
    {
        $this->factory = $factory;
        return $this;
    }

    /**
     * @return array
     * @noinspection PhpUnused
     * @noinspection UnknownInspectionInspection
     */
    public function getImplements(): array
    {
        return $this->implements;
    }

    /**
     * @param array $implements
     * @return ClassBuilder
     * @noinspection PhpUnused
     * @noinspection UnknownInspectionInspection
     */
    public function setImplements(array $implements): ClassBuilder
    {
        $newInterfaces = collect($implements)
            ->filter(
                fn ($i) => !collect($this->implements)
                    ->first(fn ($p) => $p === $i)
            );

        $this->implements = array_merge($this->implements, $newInterfaces->toArray());

        return $this;
    }

    /**
     * @return DocBlockBuilder
     */
    public function getDocBuilder(): DocBlockBuilder
    {
        return $this->docBuilder;
    }

    /**
     * @param DocBlockBuilder $docBuilder
     * @return ClassBuilder
     */
    public function setDocBuilder(DocBlockBuilder $docBuilder): ClassBuilder
    {
        $this->docBuilder = $docBuilder;
        return $this;
    }

    /**
     * @param $propertyName
     * @return PropertyBuilder
     */
    public function getNewPropertyBuilder($propertyName): PropertyBuilder
    {
        return new PropertyBuilder($propertyName);
    }

    /**
     * @param $classConst
     * @return ClassConstBuilder
     */
    public function getNewClassConstBuilder($classConst): ClassConstBuilder
    {
        if (is_string($classConst)) {
            $classConst = $this->inlineAssign($this->const($classConst), $this->nopExpr());
        }

        return new ClassConstBuilder($classConst);
    }

    /**
     * @param $methodName
     * @return MethodBuilder
     */
    public function getNewMethodBuilder($methodName): MethodBuilder
    {
        return new MethodBuilder($methodName);
    }

    /**
     * @return string
     */
    public function getFilename(): string
    {
        return $this->filename;
    }

    /**
     * @param string $filename
     * @return ClassBuilder
     */
    public function setFilename(string $filename): ClassBuilder
    {
        $this->filename = $filename;

        return $this;
    }

    /**
     * @param null $path
     * @return bool
     */
    public function toDisk($path = null): bool
    {
        $contents = $this->getContents();

        if (trim($contents) === '') {
            throw new RuntimeException('Class cannot be empty!');
        }

        return File::put($path ?? $this->outputDir . '/' . $this->filename, $contents) !== false;
    }

    /**
     * @return string|string[]
     */
    public function getContents(): string
    {
        if (!$this->getClass() || !$this->getNamespaceNode()) {
            return '';
        }

        $uniqueSortStmts = SortingHelpers::sortUseStmts($this->useStmts);

        foreach ($uniqueSortStmts as $uniqueSortStmt) {
            $stmt = $uniqueSortStmt['stmt'];
            $as = $uniqueSortStmt['as'] ?? null;

            if (!$as) {
                $this->stmt($this->factory->use($stmt));
            } else {
                $this->stmt($this->factory->use($stmt)->as($as));
            }
        }

        $this->getClass()->implement(...$this->implements);

        if (count($this->traits)) {
            $useTrait = $this->factory->useTrait(...$this->traits);

            $this->getClass()->addStmt($useTrait);
        }

        foreach ($this->consts as $const) {
            $this->getClass()->addStmt($const);
        }

        foreach ($this->props as $prop) {
            $this->getClass()->addStmt($prop);
        }

        foreach ($this->methods as $method) {
            $this->getClass()->addStmt($method);
        }

        $this->stmt($this->getClass());

        $statements = [$this->getNamespaceNode()->getNode()];
        $prettyPrinter = new Standard(['shortArraySyntax' => true]);

        $code = $prettyPrinter->prettyPrintFile($statements);

        if ($this->removeNamespace) {
            /** @noinspection PhpPossiblePolymorphicInvocationInspection */
            $code = str_replace(
                'namespace ' .
                $this->getNamespaceNode()
                    ->getNode()
                    ->name->toString() .
                ';' .
                PHP_EOL .
                PHP_EOL,
                '',
                $code
            );
        }

        return $code;
    }

    /**
     * @return Class_
     */
    public function getClass(): Class_
    {
        return $this->class;
    }

    /**
     * @return Namespace_
     */
    public function getNamespaceNode(): Namespace_
    {
        return $this->nsNode;
    }

    /**
     * @return ClassBuilder
     */
    public function reset(): ClassBuilder
    {
        $this->useStmts = [];
        $this->methods = [];
        $this->methodBuilders = [];
        $this->props = [];
        $this->implements = [];
        return $this;
    }

    /**
     * @param $trait
     * @return bool
     */
    public function hasTrait($trait): bool
    {
        return in_array($trait, $this->traits, true);
    }

    /**
     * @param string $use
     * @return ClassBuilder
     */
    public function removeUse(string $use): ClassBuilder
    {
        foreach ($this->useStmts as $index => $useStmt) {
            if ($useStmt['stmt'] === $use) {
                unset($this->useStmts[$index]);
            }
        }

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
     * @param array $processors
     * @return ClassBuilder
     */
    public function setProcessors(array $processors): ClassBuilder
    {
        $this->processors = $processors;
        return $this;
    }

    /**
     * @return array
     */
    public function getTraits(): array
    {
        return $this->traits;
    }

    /**
     * @param array $traits
     * @return ClassBuilder
     */
    public function setTraits(array $traits): ClassBuilder
    {
        $this->traits = [];

        return $this->addTraits($traits);
    }

    /**
     *
     */
    public function updateClassDefinition(): ClassBuilder
    {
        $classDefinition = $this->classDefinition;

        if ($classDefinition) {
            $class = null;

            if (array_key_exists('name', $classDefinition)) {
                $class = $this->class($classDefinition['name']);
            }

            if ($class && array_key_exists('extend', $classDefinition)) {
                $class->extend($classDefinition['extend']);
            }

            if ($class && array_key_exists('namespace', $classDefinition)) {
                $this->setNamespaceNode($classDefinition['namespace']);
            } else if ($class && property_exists($this, 'namespace')) {
                $this->setNamespaceNode($this->namespace);
            }
        }

        return $this;
    }

    /**
     * @param string $className
     * @param string|null $namespace
     * @param string|null $extend
     * @return ClassBuilder
     * @noinspection PhpUnused
     * @noinspection UnknownInspectionInspection
     */
    public function setClassDefinition(
        string $className,
        string $namespace = null,
        string $extend = null
    ): ClassBuilder
    {
        $this->classDefinition['name'] = $className;

        if ($namespace) {
            $this->classDefinition['namespace'] = $namespace;
        }

        if ($extend) {
            $this->classDefinition['extend'] = $extend;
        }

        return $this;
    }

    /**
     * @param string $name
     * @return Class_
     */
    protected function class(string $name): Class_
    {
        $class = $this->factory->class($name);

        $this->class = $class;

        return $class;
    }

    /**
     * @param $statement
     * @return Namespace_|null
     */
    protected function stmt($statement): ?Namespace_
    {
        if (!$this->getNamespaceNode()) {
            throw new RuntimeException(
                'Make sure the namespace is defined before adding a statement.'
            );
        }

        return $this->getNamespaceNode()->addStmt($statement);
    }

    /**
     * @param $eventClass
     * @param mixed ...$args
     * @return ClassBuilder
     */
    protected function fireBuilderEvent($eventClass, ...$args): ClassBuilder
    {
        $eventClass::setOutputDir($this->outputDir);

        $eventClass::dispatch($this, ...$args);

        return $this;
    }

    /**
     * @param bool $suppressPHPStormInspectionWarnings
     * @return ClassBuilder
     * @noinspection PhpUnused
     * @noinspection UnknownInspectionInspection
     */
    protected function setSuppressPHPStormInspectionWarnings(
        bool $suppressPHPStormInspectionWarnings
    ): ClassBuilder
    {
        $this->suppressPHPStormInspectionWarnings = $suppressPHPStormInspectionWarnings;
        return $this;
    }

    /**
     * @return bool
     */
    protected function shouldSuppressPHPStormInspectionWarnings(): bool
    {
        return $this->suppressPHPStormInspectionWarnings;
    }
}
