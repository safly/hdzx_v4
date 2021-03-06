<?php
namespace backend\controllers;

use Yii;
use yii\base\InvalidParamException;
use yii\web\BadRequestHttpException;
use yii\web\Controller;
use yii\web\Response;
use yii\filters\AccessControl;

use common\models\entities\BaseUser;
use backend\models\LockQueryForm;
use backend\models\LockForm;
use common\filter\PrivilegeRule;

/**
 * Lock controller
 */
class LockController extends Controller
{
    /**
     * @inheritdoc
     */
    public function behaviors()
    {
        return [
            'access' => [
                'class' => AccessControl::className(),
                'only' => [
                    'lock-page', 'getlocks', 'submitlock', 'deletelock', 'applylock',
                ],
                'rules' => [
                    [
                        'class' => PrivilegeRule::className(),
                        'actions' => [
                            'lock-page', 'getlocks', 'submitlock', 'deletelock', 'applylock',
                        ],
                        'roles' => ['@'],
                        'allow' => true,
                        'privileges' => [BaseUser::PRIV_ADMIN],
                    ],
                ],
            ],
        ];
    }

    /**
     * @inheritdoc
     */
    public function actions()
    {
        return [
            'error' => [
                'class' => 'yii\web\ErrorAction',
            ],
        ];
    }

    /**
     * 房间锁-页面
     *
     * @return mixed
     */
    public function actionLockPage()
    {
        return $this->render('/page/lock', [
            'type' => 'admin',
        ]);
    }

    /**
     * 查询房间锁
     *
     * @return mixed
     */
    public function actionGetlocks() {
        Yii::$app->response->format = Response::FORMAT_JSON;

        $data = Yii::$app->request->get();
        $model = new LockQueryForm(['scenario' => 'getLocks']);
        $model->load($data, '');
        $resdata = $model->getLocks();
        return $resdata;
    }

    /**
     * 新增房间锁
     *
     * @return mixed
     */
    public function actionSubmitlock() {
        Yii::$app->response->format = Response::FORMAT_JSON;

        $reqData = Yii::$app->request->post(); 
        $model = new LockForm();
        if (empty($reqData['lock_id'])) {
            $model->scenario =  LockForm::SCENARIO_ADD_LOCK;
        } else {
             $model->scenario =  LockForm::SCENARIO_EDIT_LOCK;
        }
        $model->load($reqData, '');
        $resData = $model->submitLock();
        return [
            'message' => $resData,
        ];
    }

    /**
     * 新增房间锁
     *
     * @return mixed
     */
    public function actionDeletelock() {
        Yii::$app->response->format = Response::FORMAT_JSON;

        $reqData = Yii::$app->request->post(); 
        $model = new LockForm();
        $model->scenario =  LockForm::SCENARIO_DELETE_LOCK;

        $model->load($reqData, '');
        $resData = $model->deleteLock();
        return [
            'message' => $resData,
        ];
    }

    /**
     * 新增房间锁
     *
     * @return mixed
     */
    public function actionApplylock() {
        Yii::$app->response->format = Response::FORMAT_JSON;

        $reqData = Yii::$app->request->post(); 
        $model = new LockForm();
        $model->scenario =  LockForm::SCENARIO_APPLY_LOCK;

        $model->load($reqData, '');
        $resData = $model->applyLock();
        return [
            'message' => $resData,
        ];
    }
}
