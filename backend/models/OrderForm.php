<?php
namespace backend\models;

use Yii;
use yii\base\Model;
use yii\base\UserException;

use common\behaviors\ErrorBehavior;
use common\models\entities\Department;
use common\models\entities\Order;
use common\models\entities\Room;
use common\services\OrderService;

/**
 * OrderSubmit form
 */
class OrderForm extends Model {
    public $order_id;
    public $comment;

    const SCENARIO_ISSUE      = 'issueOrder';

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
    public function scenarios(){
        $scenarios = parent::scenarios();
        $scenarios[static::SCENARIO_ISSUE] = ['order_id','comment'];
        return $scenarios;
    }

    /**
     * @inheritdoc
     */
    public function rules() {
        return [
            [['order_id',], 'required'],
        ];
    }


    /**
     * 取消申请.
     *
     * @return Order|false 是否成功
     */
    public function issueOrder() {
        if (!$this->validate()) {
            throw new UserException($this->getErrorMessage());
        }

        $order = Order::findOne($this->order_id);
        if(empty($order)){
            throw new UserException('申请不存在');
        }

        $user = Yii::$app->user->getIdentity()->getUser();

    
        OrderService::issueOrder($order, $user, $this->comment);
        return '发放开门条成功';
    }
}
