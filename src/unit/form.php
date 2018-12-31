<?php
/* @var $this yii\web\View */
/* @var $form yii\widgets\ActiveForm */
/* @var $generator yii\gii\generators\module\Generator */
?>
<div class="module-form">
    <?php
    echo $form->field($generator, 'modelClass');
    echo $form->field($generator, 'testClass');
    echo $form->field($generator, 'baseClass');
    ?>
</div>