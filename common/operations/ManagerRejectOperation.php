<?php
/**
 * @link http://www.j3l11234.com/
 * @copyright Copyright (c) 2015 j3l11234
 * @author j3l11234@j3l11234.com
 */

namespace common\operations;

use Yii;
use yii\base\Component;
use yii\base\UserException;

use common\helpers\Error;
use common\models\entities\Order;
use common\models\entities\OrderOperation;
use common\models\entities\User;
use common\services\RoomService;
use common\services\ApproveService;

/**
 * 负责人审批驳回 操作
 *
 */
class ManagerRejectOperation extends BaseOrderOperation {

    protected static $type = OrderOperation::TYPE_MANAGER_REJECT;
    protected static $opName = '负责人审批驳回';
    
    /**
     * @inheritdoc
     * 该方法将会检查用户是否拥有审批权限
     */
    protected function checkAuth() {
        if (!$this->user->checkPrivilege(User::PRIV_APPROVE_MANAGER_ALL)) {
            if(!$this->user->checkPrivilege(User::PRIV_APPROVE_MANAGER_DEPT)){
                throw new UserException('该账号无负责人审批权限', Error::AUTH_FAILED);
            }else{
                if(!in_array($this->order->dept_id, ApproveService::queryUserDepts($this->user))) {
                    throw new UserException('该账号对该申请无负责人审批权限', Error::AUTH_FAILED);
                }
            }
        }
    }

    /**
     * @inheritdoc
     */
    protected function checkPreStatus() {
        if ($this->order->status != Order::STATUS_MANAGER_PENDING){
            throw new UserException('申请状态异常', Error::INVALID_ORDER_STATUS);
        }
    }

    /**
     * @inheritdoc
     */
    protected function checkRoomTable() {
    }

    /**
     * @inheritdoc
     */
    protected function applyRoomTable() {
        $hours = $this->order->hours;
        $order_id = $this->order->id;
        $this->roomTable->removeOrdered($order_id);
        $this->roomTable->addRejected($order_id, $hours);
    }

    /**
     * @inheritdoc
     */
    protected function setPostStatus() {
        $this->order->status = Order::STATUS_MANAGER_REJECTED;
    }

}