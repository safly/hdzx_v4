<?php
namespace backend\models;

use Yii;
use yii\base\Model;
use common\behaviors\ErrorBehavior;
use common\services\LockService;

/**
 * LockQuery form
 */
class LockQueryForm extends Model {
    public $status;

    /**
     * @inheritdoc
     */
    public function behaviors() {
        return [
            ErrorBehavior::className()
        ];
    }

    /**
     * @inheritdoc
     */
    public function scenarios() {
        $scenarios = parent::scenarios();
        $scenarios['getLocks'] = ['status',];
        return $scenarios;
    }

    /**
     * @inheritdoc
     */
    public function rules() {
        return [
        ];
    }

    /**
     * 查询房间锁
     *
     * @return json
     */
    public function getLocks() {
        $data = LockService::getLockList(FALSE, FALSE);

        return $data;
    }
}
