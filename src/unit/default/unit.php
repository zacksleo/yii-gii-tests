<?php
use yii\helpers\StringHelper;

echo "<?php\n";
?>
namespace <?= StringHelper::dirname(ltrim($generator->testClass, '\\')) ?>;

use <?= ltrim($generator->modelClass, '\\') ?>;
/**
 *
 */
class <?= $testClass ?> extends <?= StringHelper::basename($generator->baseClass) . "\n" ?>
{
    public function testCreate()
    {
        $model = new <?= $testClass ?>();
        $model->name = '片断代码';
        $model->content = '片断内容，支持<span>html</span>';
        $model->category_id = 1;
        $model->number = 24;
        $this->assertTrue($model->save());
    }
}
