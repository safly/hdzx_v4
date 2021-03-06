<?php

namespace common\tests\unit\models\entities;

use Yii;
use common\models\entities\Order;
use common\fixtures\Order as OrderFixture;
use common\helpers\DateRoom;

/**
 * Order test
 */
class OrderTest extends \Codeception\Test\Unit
{
    /**
     * @var \common\tests\UnitTester
     */
    protected $tester;


    public function _before()
    {
        $this->tester->haveFixtures([
            'order' => [
                'class' => OrderFixture::className(),
                'dataFile' => codecept_data_dir() . 'order.php'
            ]
        ]);
    }

    public function testRW() {
        $modelData = [
            'date' => '2015-01-03',
            'room_id' => '99',
            'hours' => [8,9,10],
            'user_id' => '1',
            'dept_id' => 1,
            'type' => '1',
            'status' => '1',
            'submit_time' => 12312312,
            'data' => [
                'name' => '李鹏翔',
                'student_no' => '12301119',
                'phone' => '15612322',
                'title' => '学习',
                'content' => '学习',
                'number' => '1',
                'secure' => '做好了',
            ],
            'issue_time' => '1',
        ];
        $order = new Order();
        $order->load($modelData, '');
        expect('save()', $order->save())->true();

        $newOrder = Order::findOne($order->getPrimaryKey());
        expect('order->date', $newOrder->date)->equals($modelData['date']);
        expect('order->room_id', $newOrder->room_id)->equals($modelData['room_id']);
        expect('order->user_id', $newOrder->user_id)->equals($modelData['user_id']);
        expect('order->type', $newOrder->type)->equals($modelData['type']);
        expect('order->status', $newOrder->status)->equals($modelData['status']);
        expect('order->submit_time', $newOrder->submit_time)->equals($modelData['submit_time']);
        expect('order->hours', $newOrder->hours)->equals($modelData['hours']);
        expect('order->dept_id', $newOrder->dept_id)->equals($modelData['dept_id']);
        expect('order->data', $newOrder->data)->equals($modelData['data']);
        expect('order->issue_time', $newOrder->issue_time)->equals($modelData['issue_time']);      
    }

    public function testFields() {
        $modelData = [
            'date' => '2015-01-03',
            'room_id' => '99',
            'hours' => [8,9,10],
            'user_id' => '1',
            'dept_id' => 1,
            'type' => '1',
            'status' => '1',
            'submit_time' => 12312312,
            'data' => [
                'name' => '李鹏翔',
                'student_no' => '12301119',
                'phone' => '15612322',
                'title' => '学习',
                'content' => '学习',
                'number' => '1',
                'secure' => '做好了',
            ],
            'issue_time' => '1',
        ];
        $order = new Order();
        $order->load($modelData, '');

        $exportData = $order->toArray(['date', 'room_id', 'hours', 'user_id', 'dept_id', 'type', 'status', 'submit_time', 'data', 'issue_time']);
        expect('exportData', $exportData)->equals($modelData);
    }

    public function testFindByDateRoom() {
        $orderList = Order::findByDateRoom('2015-12-01', 301);
        expect('the count', count($orderList))->equals(2);

        $dateRooms = [new DateRoom('2015-12-01','301'),new DateRoom('2015-12-02','301')];
        $orderList = Order::findByDateRooms($dateRooms,['id', 'date', 'room_id'], true, 0);
        expect('the count', count($orderList))->equals(4);
    }

    public function testHours2Range() {
        $hours = [8,9,10,11];
        expect('range ', Order::hours2Range($hours))->equals(['start_hour' => 8, 'end_hour' => 12]);
    }

}
