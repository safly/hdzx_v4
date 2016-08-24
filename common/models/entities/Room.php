<?php
/**
 * @link http://www.j3l11234.com/
 * @copyright Copyright (c) 2015 j3l11234
 * @author j3l11234@j3l11234.com
 */

namespace common\models\entities;

use Yii;
use yii\behaviors\TimestampBehavior;
use yii\db\ActiveRecord;
use common\behaviors\JsonBehavior;

/**
 * 房间
 *
 * @property integer $id
 * @property integer $number 房间号
 * @property string $name 房间名
 * @property integer $type 房间类型
 * @property string $data
 * ```php
 * $data = [
 *     'secure' => 1, //是否需要填写安全信息
 *     'by_week' => 1, //按周开放
 *     'open_time' => '07:00:00', //开放时间
 *     'max_before' => 30, //最大提前
 *     'min_before' => 5, //最小提前申请
 *     'max_hour' => 2, //单次申请最长时间
 * ]
 * ```  
 * @property integer $align 排序依据
 * @property integer $status 房间状态
 * @property integer $created_at
 * @property integer $updated_at
 */
class Room extends ActiveRecord {
    /**
     * 房间类型 琴房
     */
    const TYPE_SIMPLE     = 01;
    /**
     * 房间类型 活动室
     */
    const TYPE_ACTIVITY   = 02;

    /**
     * 房间状态 关闭
     */
    const STATUS_CLOSE  = 00;
    /**
     * 房间状态 开放申请
     */
    const STATUS_OPEN   = 01;


    /**
     * @inheritdoc
     */
    public static function tableName() {
        return '{{%room}}';
    }

    /**
     * @inheritdoc
     */
    public function behaviors() {
        return [
            [
                'class' => TimestampBehavior::className(),
            ],[
                'class' => JsonBehavior::className(),
                'attributes' => ['data'],
            ],
        ];
    }

    /**
     * @inheritdoc
     */
    public function rules() {
        return [
            [['id', 'number', 'name', 'type', 'data', 'align', 'status'], 'safe'],
        ];
    }

    /**
     * 得到开放的日期范围
     *
     * @param int $max_before 最大提前日期
     * @param int $min_before 最小提前日期
     * @param int $by_week 是否按周开放，1为是
     * @param string $open_time 开放时间，默认为[00:00:00]
     * @param int $now 参考时间，默认为当前时间
     * @return array 返回的格式
     * [
     *      'start' => $limitStart,
     *      'end' => $limitEnd
     *      'expired' => 过期时间
     * ]
     */
    public static function getDateRange($max_before, $min_before, $by_week, $open_time = null, $now = null) {
        $now = $now === null ? time() : $now;
        $open_time = $open_time === null ? '0:0:0' : $open_time;

        $month = date("m", $now);
        $day = date("d", $now);
        $year = date("Y", $now);

        $limitStart = mktime(0, 0, 0, $month, $day + $min_before, $year);

        //开放时间判断，如果未到达开放时间，则按照前一天算
        $open_now = strtotime(date('Y-m-d',$now).' '.date('H:i:s',strtotime($open_time)));
        if ($now < $open_now){
            $day--;
            $expired = $open_now - $now;
        } else {
            $expired = mktime(0, 0, 0, $month, $day + 1, $year) - $now;
        }

        $limitEnd = mktime(23, 59, 59, $month, $day + $max_before, $year);
        //如果是按周开发则自动开放到本周日
        if($by_week == 1) {
            $weekDay = date('w', $limitEnd);
            $max_before += (7 - $weekDay) % 7;
            $limitEnd = mktime(23, 59, 59, $month, $day + $max_before, $year);
        }

        return [
            'start' => $limitStart,
            'end' => $limitEnd,
            'expired' => $expired,
        ];
    }

    /**
     * 验证日期是否在可申请范围内
     *
     * @param string $date 测试日期 形如'2015-12-15'
     * @param int $max_before 最大提前日期
     * @param int $min_before 最小提前日期
     * @param int $by_week 是否按周开放，1为是
     * @param string $open_time 开放时间，默认为[00:00:00]
     * @param int $now 参考时间，默认为当前时间
     * @return boolean 是否可以申请
     */
    public static function checkOpen($date, $max_before, $min_before, $by_week, $open_time = null, $now = null) {
        $date = strtotime($date);
        $range = self::getDateRange($max_before, $min_before, $by_week, $open_time, $now);

        return ($date >= $range['start'] && $date <= $range['end']);
    }

    /**
     * 得到所有开启的房间
     *
     * @param boolean $onlyId 仅获取id
     * @return static|null
     */
    public static function getOpenRooms($onlyId = false) {
        $find = static::find()
            ->where(['status' => self::STATUS_OPEN])
            ->orderBy('align');

        if ($onlyId) {
            $result = $find->select(['id'])->asArray()->all();
            $data = [];
            foreach ($result as $room) {
                $data[] = $room['id'];
            }
        } else {
            $data = $find->all();
        }

        return $data;
    }
}
