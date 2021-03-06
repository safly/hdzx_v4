<?php

use yii\helpers\Html;
use yii\grid\DataColumn;
use yii\grid\GridView;

use common\models\entities\BaseUser;

/* @var $this yii\web\View */
/* @var $dataProvider yii\data\ActiveDataProvider */

$this->title = 'Users';
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="user-index">

    <h1><?= Html::encode($this->title) ?></h1>

    <p>
        <?= Html::a('创建用户', ['create'], ['class' => 'btn btn-success']) ?>
    </p>
    
    <?php $statusTexts = BaseUser::getStatusTexts(); ?>
    <?= GridView::widget([
        'dataProvider' => $dataProvider,
        'columns' => [
            'id',
            'username',
            //'auth_key',
            //'password_hash',
            //'password_reset_token',
            'email:email',
            'alias',
            [
                'class' => DataColumn::className(),
                'attribute' => 'manage_depts',
                'content' => function ($model, $key, $index, $column){
                    return json_encode($model->manage_depts);
                },
            ],
            [
                'class' => DataColumn::className(),
                'attribute' => 'status',
                'content' => function ($model, $key, $index, $column) use ( $statusTexts ) {
                    return isset($statusTexts[$model->status]) ? $statusTexts[$model->status] : '未知状态';   
                },
            ],

            ['class' => 'yii\grid\ActionColumn'],
        ],
    ]); ?>

</div>
