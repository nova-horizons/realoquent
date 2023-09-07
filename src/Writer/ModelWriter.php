<?php

namespace NovaHorizons\Realoquent\Writer;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Nette\PhpGenerator\ClassType;
use Nette\PhpGenerator\PhpNamespace;
use Nette\PhpGenerator\PsrPrinter;
use NovaHorizons\Realoquent\DataObjects\Table;
use NovaHorizons\Realoquent\RealoquentHelpers;

class ModelWriter
{
    protected readonly Table $table;

    protected readonly string $namespace;

    protected readonly string $model;

    protected readonly string $baseModel;

    protected readonly string $baseNamespace;

    protected readonly ?string $fullModel;

    protected readonly string $baseModelDir;

    protected readonly string $modelDir;

    public function __construct(Table $table, string $modelNamespace, string $modelDir,
    ) {
        $this->table = $table;
        // TODO How to handle tables that shouldn't have a model?
        if (! isset($this->table->model)) {
            $this->fullModel = $modelNamespace.ucfirst(Str::studly($this->table->name));
        } else {
            $this->fullModel = $this->table->model;
        }
        $this->namespace = Str::beforeLast($this->fullModel, '\\');
        $this->model = Str::afterLast($this->fullModel, '\\');
        $this->baseModel = 'Base'.$this->model;
        $this->baseNamespace = $this->namespace.'\\BaseModels';
        $this->baseModelDir = $modelDir.'/BaseModels';
        $this->modelDir = $modelDir;
        RealoquentHelpers::validateDirectory($this->baseModelDir);
    }

    /**
     * @throws \Throwable
     */
    protected function writeBaseModel(): string
    {
        $contents = $this->buildBaseModel();
        $path = $this->baseModelDir.DIRECTORY_SEPARATOR.$this->baseModel.'.php';
        $result = file_put_contents($path, $contents);
        throw_unless($result, new \RuntimeException('Failed to write base model file: '.$this->baseModel.'.php'));

        return $path;
    }

    /**
     * @return string[]
     *
     * @throws \Throwable
     */
    public function writeModel(): array
    {
        $basePath = $this->writeBaseModel();
        $contents = $this->buildModel();
        $path = $this->modelDir.DIRECTORY_SEPARATOR.$this->model.'.php';
        $result = file_put_contents($path, $contents);
        throw_unless($result, new \RuntimeException('Failed to write model file: '.$this->model.'.php'));

        return [$basePath, $path];
    }

    protected function buildModel(): string
    {
        $namespace = new PhpNamespace($this->namespace);

        if (class_exists($this->fullModel)) {
            /** @var ClassType $class */
            $class = ClassType::from($this->fullModel, withBodies: true);
        } else {
            $class = new ClassType($this->model);
        }
        $class->setExtends($this->baseNamespace.'\\'.$this->baseModel);

        // Remove the things Realoquent manages in the Base model
        $class->removeProperty('table')
            ->removeProperty('primaryKey')
            ->removeProperty('keyType')
            ->removeProperty('incrementing')
            ->removeProperty('fillable')
            ->removeProperty('guarded')
            ->removeProperty('casts');

        $namespace->add($class);

        return "<?php\n\n".(new PsrPrinter())->printNamespace($namespace);
    }

    protected function buildBaseModel(): string
    {
        $namespace = new PhpNamespace($this->baseNamespace);
        $namespace->addUse(Builder::class)
            ->addUse(Model::class);

        $class = new ClassType($this->baseModel);
        $class->setAbstract()
            ->setExtends(Model::class);

        $class->addComment('################################################');
        $class->addComment('### AUTO-GENERATED FILE. DO NOT MAKE CHANGES HERE');
        $class->addComment('### Make changes in schema.php');
        $class->addComment('################################################');
        $class->addComment('');
        foreach ($this->table->getColumns() as $name => $column) {
            $phpType = $column->getPhpType();
            // Trim leading ? if it is nullable
            $typeClass = trim($phpType, '?');
            // If the type is a class, add a use statement and trim the namespace
            if (class_exists($typeClass)) {
                $namespace->addUse($typeClass);
                $endClass = Str::afterLast($phpType, '\\');
                $phpType = str_replace($typeClass, $endClass, $phpType);
            }

            $class->addComment("@property {$phpType} \${$name}");

        }
        foreach ($this->table->getColumns() as $name => $column) {
            $queryName = Str::studly($name);
            $paramName = lcfirst(Str::studly($name));
            $class->addComment("@method static Builder|\\{$this->fullModel} where{$queryName}(\${$paramName})");
        }
        $class->addComment("@method static Builder|\\{$this->fullModel} newModelQuery()");
        $class->addComment("@method static Builder|\\{$this->fullModel} newQuery()");
        $class->addComment("@method static Builder|\\{$this->fullModel} query()");
        $class->addComment("@mixin Builder<\\{$this->fullModel}>");

        $class->addProperty('table', $this->table->name)->setProtected();
        if (isset($this->table->primaryKey)) {
            $class->addProperty('primaryKey', $this->table->primaryKey)->setProtected();
            $class->addProperty('keyType', $this->table->keyType)->setProtected();
        }
        $class->addProperty('incrementing', $this->table->incrementing ?? false)->setPublic();
        $class->addProperty('fillable', $this->table->getFillableColumns())->setProtected();
        $class->addProperty('guarded', $this->table->getGuardedColumns())->setProtected();
        $class->addProperty('casts', $this->table->getCastColumns())->setProtected();

        $namespace->add($class);

        return "<?php\n\n".(new PsrPrinter())->printNamespace($namespace);
    }
}
