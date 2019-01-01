<?php
namespace zacksleo\yii\gii\tests\unit;

use Yii;
use yii\gii\CodeFile;
use yii\db\Schema;

class Generator extends yii\gii\generators\model\Generator
{
    public $modelClass;
    public $testClass;
    public $baseClass;

    public function getName()
    {
        return 'Unit Test Generator';
    }

    public function getDescription()
    {
        return 'Generate unit tests codes for Codeception.';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return array_merge(parent::rules(), [
            [['modelClass', 'testClass', 'baseClass'], 'filter', 'filter' => 'trim'],
            [['modelClass', 'testClass', 'baseClass'], 'required'],
            ['testClass', 'match', 'pattern' => '/^[\w\\\\]*Test$/', 'message' => 'Only word characters and backslashes are allowed, and the class name must end with "Controller".'],
            ['testClass', 'validateNewClass'],
            [['modelClass'], 'validateModelClass'],
            ['baseClass', 'match', 'pattern' => '/^[\w\\\\]*$/', 'message' => 'Only word characters and backslashes are allowed.'],
        ]);
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'baseClass' => 'Base Class',
            'modelClass' => 'Model Class',
            'testClass' => 'Test Class',
        ];
    }
    /**
     * @inheritdoc
     */
    public function hints()
    {
        return [
            'modelClass' => 'This is the ActiveRecord class associated with the table that CRUD will be built upon.
                You should provide a fully qualified class name, e.g., <code>app\models\Post</code>.',
            'testClass' => 'This is the class name of the TestCase.
                You should provide a fully qualified class name, e.g., <code>tests\unit\common\models\Post</code>.',
            'baseClass' => 'This is the class that the new TestCase class will extend from. Please make sure the class exists and can be autoloaded.',
        ];
    }

    /**
     * Checks if model class is valid
     */
    public function validateModelClass()
    {
        /* @var $class ActiveRecord */
        $class = $this->modelClass;
        $pk = $class::primaryKey();
        if (empty($pk)) {
            $this->addError('modelClass', "The table associated with $class must have primary key(s).");
        }
    }

    /**
     * @param $table Table schema
     *
     * @return array Attributes containing all required model's information for test generator
     */
    public function generateAttributes($table)
    {
        $labels = $this->generateLabels($table);
        $attributes = [];
        foreach ($table->columns as $column) {
            $label = $column->name;
            if (isset($labels[$column->name])) {
                $label = $labels[$column->name];
            }
            $attribute = [];
            $attribute['name'] = $column->name;
            $attribute['null'] = $column->allowNull;
            $attribute['size'] = $column->size;
            $attribute['primary'] = $column->isPrimaryKey;
            $attribute['label'] = $label;
            if ($column->autoIncrement) {
                $attribute['autoincrement'] = 'true';
            }
            if (!$column->allowNull && $column->defaultValue === null) {
                $attribute['required'] = 'true';
            }
            switch ($column->type) {
                case Schema::TYPE_SMALLINT:
                case Schema::TYPE_INTEGER:
                case Schema::TYPE_BIGINT:
                    $attribute['type'] = 'integer';
                    break;
                case Schema::TYPE_BOOLEAN:
                    $attribute['type'] = 'boolean';
                    break;
                case Schema::TYPE_FLOAT:
                case 'double': // Schema::TYPE_DOUBLE, which is available since Yii 2.0.3
                case Schema::TYPE_DECIMAL:
                case Schema::TYPE_MONEY:
                    $attribute['type'] = 'number';
                    break;
                case Schema::TYPE_DATE:
                    $attribute['type'] = 'date';
                case Schema::TYPE_TIME:
                    $attribute['type'] = 'time';
                case Schema::TYPE_DATETIME:
                    $attribute['type'] = 'datetime';
                case Schema::TYPE_TIMESTAMP:
                    $attribute['type'] = 'timestamp';
                    break;
                default: // strings
                    $attribute['type'] = 'string';
            }
            $attributes[] = $attribute;
        }
        return $attributes;
    }


    /**
     * @return \yii\db\Connection the DB connection from the DI container or as application component specified by [[db]]
     */
    protected function getDbConnection()
    {
        if (Yii::$container->has($this->db)) {
            return Yii::$container->get($this->db);
        } else {
            return Yii::$app->get($this->db);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function generate()
    {
        $files = [];
        // $relations = $this->generateRelations();
        $db = $this->getDbConnection();
        $class = $this->modelClass;
        $classTableNameMethod = 'tableName';
        $this->tableName = $class::$classTableNameMethod();
        foreach ($this->getTableNames() as $tableName) {
            $className = $this->generateClassName($tableName);
            $tableSchema = $db->getTableSchema($tableName);
            $params = [
                'tableName' => $tableName,
                'className' => $className,
                'modelClass' => $this->modelClass,
                'controllerClass' => $this->controllerClass,
                'labels' => $this->generateLabels($tableSchema),
                'rules' => $this->generateRules($tableSchema),
                'attributes' => $this->generateAttributes($tableSchema),
                //TODO: Add unit tests for relations
                //'relations'      => isset($relations[$tableName]) ? $relations[$tableName] : [],
                'ns' => $this->ns,
            ];
            $controllerFile = Yii::getAlias('@' . str_replace('\\', '/', ltrim($this->controllerClass, '\\')) . '.php');
            $files[] = new CodeFile(
                Yii::getAlias('@app/..' . $this->codeceptionPath . str_replace('\\', '/', $this->ns)) . '/' . $this->baseClassPrefix . $className . $this->baseClassSuffix . 'UnitTest.php',
                $this->render('unit.php', $params)
            );
        }
        return $files;
    }
}
