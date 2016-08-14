<?php
namespace frontend\controllers;

use Yii;
use yii\base\InvalidParamException;
use yii\web\BadRequestHttpException;
use yii\web\Controller;
use yii\web\Response;
use yii\filters\VerbFilter;
use yii\filters\AccessControl;
use yii\filters\Cors;
use common\services\RoomService;
use frontend\models\OrderQueryForm;
use frontend\models\OrderSubmitForm;

/**
 * Order controller
 */
class OrderController extends Controller
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
                    'order-page', 'myorder-page', 
                    'getrooms', 'getdepts', 'getroomtables', 'getroomuse', 'getmyorders', 'submitorder'],
                'rules' => [
                    [
                        'actions' => [
                            'order-page', 'myorder-page', 
                            'getrooms', 'getdepts', 'getroomtables', 'getroomuse', 'getmyorders', 'submitorder'],
                        'allow' => true,
                        'roles' => ['@'],
                    ],
                    [
                        'actions' => [''],
                        'allow' => true,
                        'roles' => ['?'],
                    ],
                ],
            ],
            'verbs' => [
                'class' => VerbFilter::className(),
                'actions' => [
                    'getrooms' => ['get'],
                    'getdepts' => ['get'],
                    'getroomtables' => ['get'],
                    'getroomuse' => ['get'],
                    'submitorder' => ['post'],
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
            'captcha' => [
                'class' => 'frontend\actions\MyCaptchaAction',
                //'fixedVerifyCode' => YII_ENV_TEST ? 'testme' : null,
                'fixedVerifyCode' => 'testme',
                'height' => 36,
                'padding' => 0,
            ],
        ];
    }

    /**
     * 预约房间-页面
     *
     * @return mixed
     */
    public function actionOrderPage()
    {
        return $this->render('/page/order');
    }

    /**
     * 我的预约-页面
     *
     * @return mixed
     */
    public function actionMyorderPage()
    {
        $dateRange = OrderQueryForm::getDateRange();
        return $this->render('/page/myorder', [
            'start_date' => date('Y-m-d'),
            'end_date' => date('Y-m-d', $dateRange['end']),
        ]);
    }

    /**
     * 查询Getroomtables
     *
     * @return mixed
     */
    public function actionGetroomtables() {
        Yii::$app->response->format = Response::FORMAT_JSON;

        $reqData = Yii::$app->request->get();
        $model = new OrderQueryForm(['scenario' => 'getRoomTables']);
        if ($model->load($reqData, '') && $model->validate() && $resData = $model->getRoomTables()) {
            return array_merge($resData, [
                'error' => 0,
            ]);
        } else {
            return [
                'error' => 1,
                'message' => $model->getErrorMessage(),
            ];
        }
    }

    /**
     * 查询room当日占用
     *
     * @return mixed
     */
    public function actionGetroomuse() {
        Yii::$app->response->format = Response::FORMAT_JSON;

        $reqData = Yii::$app->request->get();
        $model = new OrderQueryForm(['scenario' => 'getRoomUse']);
        if ($model->load($reqData, '') && $model->validate() && $resData = $model->getRoomUse()) {
            return array_merge($resData, [
                'error' => 0,
            ]);
        } else {
            return [
                'error' => 1,
                'message' => $model->getErrorMessage(),
            ];
        }
    }

    /**
     * 提交预约
     *
     * @return mixed
     */
    public function actionSubmitorder() {
        Yii::$app->response->format = Response::FORMAT_JSON;

        $reqData = Yii::$app->request->post();

        $captchaAction = $this->createAction('captcha');
        if (empty($reqData['captcha']) || !$captchaAction->validate($reqData['captcha'], false)) {
            return [
                'error' => 1,
                'message' => '验证码错误',
            ];
        }
            
        $model = new OrderSubmitForm(['scenario' => 'submitOrder']);

        if ($model->load($reqData, '') && $model->validate() && $resData = $model->submitOrder()) {
            return [
                'error' => 0,
                'message' => '提交成功',
            ];
        } else {
            return [
                'error' => 1,
                'message' => $model->getErrorMessage(),
            ];
        }
    }

    /**
     * 查询我的预约
     *
     * @return mixed
     */
    public function actionGetmyorders() {
        Yii::$app->response->format = Response::FORMAT_JSON;

        $reqData = Yii::$app->request->get();
        $model = new OrderQueryForm(['scenario' => 'getMyOrders']);
        if ($model->load($reqData, '') && $model->validate() && $resData = $model->getMyOrders()) {
            return array_merge($resData, [
                'error' => 0,
            ]);
        } else {
            return [
                'error' => 1,
                'message' => $model->getErrorMessage(),
            ];
        }
    }
}
