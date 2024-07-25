<?php

namespace NovaHorizons\Realoquent\Writer;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Nette\PhpGenerator\ClassType;
use Nette\PhpGenerator\PhpNamespace;
use Nette\PhpGenerator\PsrPrinter;
use NovaHorizons\Realoquent\DataObjects\ModelInfo;
use NovaHorizons\Realoquent\DataObjects\Table;
use NovaHorizons\Realoquent\Enums\ColumnType;
use NovaHorizons\Realoquent\RealoquentHelpers;

class ModelWriter
{
    protected readonly Table $table;

    protected readonly string $namespace;

    protected readonly string $model;

    protected readonly ?ModelInfo $modelInfo;

    protected readonly string $baseModel;

    protected readonly string $baseNamespace;

    protected readonly ?string $fullModel;

    protected readonly string $baseModelDir;

    protected readonly string $modelDir;

    public function __construct(Table $table, string $modelNamespace, string $modelDir,
    ) {
        $this->table = $table;

        if (! $table->shouldHaveModel()) {
            throw new \InvalidArgumentException('The given table should not have a model: '.$table->name);
        }

        $model = $this->table->model ?? true; // Assume if user didn't specify, that we should create a new model

        if ($model === true) {
            $this->fullModel = RealoquentHelpers::buildModelName($modelNamespace, $this->table->name);
            $this->modelInfo = null;
        } else {
            $this->fullModel = $this->table->model;
            $this->modelInfo = new ModelInfo($this->fullModel);
        }
        $this->namespace = Str::beforeLast($this->fullModel, '\\');
        $this->model = Str::afterLast($this->fullModel, '\\');
        $this->baseModel = 'Base'.$this->model;
        $this->baseNamespace = $this->namespace.'\\BaseModels'; // TODO: Make configurable, also update ModelInfo
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

        if ($this->modelInfo) {
            /** @var ClassType $class */
            $class = ClassType::from($this->fullModel, withBodies: true);
            foreach ($this->modelInfo->uses as $useName => $use) {
                $namespace->addUse($use, $useName);
            }
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
            ->removeProperty('casts')
            ->removeProperty('validation')
            ->removeProperty('validationGroups');

        $namespace->add($class);

        return "<?php\n\n".(new PsrPrinter)->printNamespace($namespace);
    }

    protected function buildBaseModel(): string
    {
        $namespace = new PhpNamespace($this->baseNamespace);
        $namespace->addUse(Builder::class)
            ->addUse(Model::class);

        $class = new ClassType($this->baseModel);
        $class->setAbstract()
            ->setExtends($this->modelInfo->extends ?? Model::class);

        $class->addComment('#####################################################################');
        $class->addComment('### AUTO-GENERATED FILE. DO NOT MAKE CHANGES HERE');
        $class->addComment('### Make changes in schema.php and run `php artisan realoquent:diff`');
        $class->addComment('#####################################################################');
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

        $class->addProperty('table', $this->table->name)->setProtected()->addComment('@var string');
        if (isset($this->table->primaryKey)) {
            $class->addProperty('primaryKey', $this->table->primaryKey)->setProtected()->addComment('@var string');
            $class->addProperty('keyType', $this->table->keyType)->setProtected()->addComment('@var string');
            $primaryCol = $this->table->getColumns()[$this->table->primaryKey];
            if ($primaryCol->type === ColumnType::uuid) {
                $class->addTrait(HasUuids::class);
            } elseif ($primaryCol->type === ColumnType::ulid) {
                $class->addTrait(HasUlids::class);
            }
        }
        $class->addProperty('incrementing', $this->table->incrementing ?? false)->setPublic()->addComment('@var bool');
        $class->addProperty('fillable', $this->table->getFillableColumns())->setProtected()->addComment('@var list<string>');
        $class->addProperty('guarded', $this->table->getGuardedColumns())->setProtected()->addComment('@var array<int, string>');
        $class->addProperty('casts', $this->table->getCastColumns())->setProtected()->addComment('@var array<string, string>');

        if (! isset($this->table->getColumns()[Model::CREATED_AT]) && ! isset($this->table->getColumns()[Model::UPDATED_AT])) {
            $class->addProperty('timestamps', false)->setPublic()->addComment('@var bool');
        }

        if (! empty($this->table->getValidation())) {

            $arrayShape = collect($this->table->getValidation())->map(fn (array $rules, string $column) => "'{$column}': string[]")->implode(', ');

            $class->addMethod('getValidation')
                ->setReturnType('array')
                ->setStatic()
                ->setBody('return self::$validation;')
                ->addComment('@return array{'.$arrayShape.'}');

            $class->addProperty('validation', $this->table->getValidation())
                ->setProtected()
                ->setStatic()
                ->setType('array')
                ->addComment('@var array{'.$arrayShape.'}');

            if (! empty($this->table->getvalidationGroups())) {
                $groupArrayShape = collect($this->table->getvalidationGroups())->map(fn (array $columns, string $group) => "'{$group}': string[]")->implode(', ');

                $class->addProperty('validationGroups', $this->table->getvalidationGroups())
                    ->setProtected()
                    ->setStatic()
                    ->setType('array')
                    ->addComment('@var array{'.$groupArrayShape.'}');

                foreach ($this->table->getvalidationGroups() as $group => $columns) {
                    $groupRules = array_intersect_key($this->table->getValidation(), array_flip($columns));

                    $columns = collect($columns)->map(fn (string $column) => "'{$column}': string[]")->implode(', ');
                    $class->addMethod('getValidationFor'.Str::studly($group))
                        ->setReturnType('array')
                        ->setStatic()
                        ->setBody('return '.RealoquentHelpers::printArray($groupRules).';')
                        ->addComment('@return array{'.$columns.'}');
                }
            }
        }

        $namespace->add($class);

        return "<?php\n\n".(new PsrPrinter)->printNamespace($namespace);
    }
}
