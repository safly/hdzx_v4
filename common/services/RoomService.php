<?php
/**
 * @link http://www.j3l11234.com/
 * @copyright Copyright (c) 2015 j3l11234
 * @author j3l11234@j3l11234.com
 */

namespace common\services;

use Yii;
use yii\base\Component;
use yii\caching\TagDependency;
use common\helpers\Error;
use common\helpers\DateRoom;
use common\models\entities\Room;
use common\models\entities\RoomTable;
use common\models\entities\Order;
use common\services\OrderService;
use common\services\LockService;

/**
 * 房间相关服务类
 * 负责RoomTable数据的读取，获取
 * 预约信息注册，房间锁信息的注册
 */
class RoomService extends Component {

    /**
     * 查询所有开放房间(带缓存)
     * @param boolean $onlyId 仅获取id
     * @param boolean $useCache 是否使用缓存
     * @return 如果onlyId未真，返回room_id的列表，否则返回room的Map
     */
    public static function getRoomList($onlyId = FALSE, $useCache = TRUE) {
        $roomList;

        //读取缓存
        $cacheMiss;
        if ($useCache) {
            $cacheKey = 'RoomList';
            $cacheData = Yii::$app->cache->get($cacheKey);
            if ($cacheData == null) {
                Yii::trace($cacheKey.':缓存失效', '数据缓存');
                $cacheMiss = TRUE;
            } else {
                Yii::trace($cacheKey.':缓存命中', '数据缓存');
                $roomList = $cacheData;
                $cacheMiss = FALSE;
            }
        } else {
            $cacheMiss = TRUE;
        }
        if($cacheMiss) {
            $rooms = Room::getOpenRooms(FALSE);
            $roomList = [
                'roomList' => array_keys($rooms),
                'rooms' => $rooms,
            ];

            //写入缓存
            $cacheKey = 'RoomList';
            Yii::$app->cache->set($cacheKey, $roomList,
                Yii::$app->params['cache.duration'],
                new TagDependency(['tags' => ['Room']]));
            Yii::trace($cacheKey.':写入缓存', '数据缓存'); 
        }

        if ($onlyId) {
            return $roomList['roomList'];
        }
        return $roomList;
    }


    /**
     * 得到房间的日期范围(带缓存)
     *
     * @param Array $room_ids
     * @param boolean $now 参考时间
     * @return Array DateRange的Map
     */
    public static function getDateRanges($room_ids, $now = null) {
        $rooms = static::getRoomList()['rooms'];
        $dateRanges = [];

        foreach ($room_ids as $room_id) {
            if (!isset($rooms[$room_id])) {
                continue;
            }
            $room = $rooms[$room_id];

            $dateRange = Room::getDateRange($room, $now);
            $dateRanges[$room_id] = $dateRange;
        }

        return $dateRanges;
    }


    /**
     * 批量获取房间可申请日期范围
     *
     * @param Array $dateRooms [日期房间]的数组
     * @param $useCache 是否使用缓存(默认为是)
     * @return Array Map形式的OpenPeriod
     */
    public static function getOpenPeriods($dateRooms, $useCache = true) {
        $openPeriods = [];

        //读取缓存
        $cacheMisses;
        if ($useCache) {
            $cacheMisses = [];
            Yii::beginProfile('OpenPeriod读取缓存', '数据缓存');
            foreach ($dateRooms as $dateRoom) {
                $cacheKey = 'OpenPeriod'.'_'.$dateRoom->key;
                $cacheData = Yii::$app->cache->get($cacheKey);
                if ($cacheData == null) {
                    Yii::trace($cacheKey.':缓存失效', '数据缓存');
                    $cacheMisses[] = $dateRoom;
                } else {
                    Yii::trace($cacheKey.':缓存命中', '数据缓存');
                    $openPeriods[$dateRoom->key] = $cacheData;
                }
            }
            Yii::endProfile('OpenPeriod读取缓存', '数据缓存');
        } else {
            $cacheMisses = $dateRooms;
        }

        //获取剩下数据(缓存miss的)
        if(count($cacheMisses) > 0) {
            //获取房间信息
            $room_ids = [];
            foreach ($cacheMisses as $dateRoom) {
                $room_ids[$dateRoom->room_id] = true;
            }
            $room_ids = array_keys($room_ids);

            $roomDatas = [];
            foreach (Room::find()->where(['in', 'id', $room_ids])
                ->select(['id', 'data'])->asArray()->each(100) as $room) {
                $roomDatas[$room['id']] = json_decode($room['data'], TRUE);
            }

            //计算范围
            $cacheNews = [];
            foreach ($cacheMisses as $dateRoom) {
                $roomData = $roomDatas[$dateRoom->room_id];
                $openPeriod = Room::getOpenPeriod($roomData, $dateRoom->date);

                $openPeriods[$dateRoom->key] = $openPeriod;
                $cacheNews[] = $dateRoom;
            }

            if ($useCache) {
                //写入缓存
                Yii::beginProfile('OpenPeriod读取缓存', '数据缓存');
                foreach ($cacheNews as $dateRoom) {
                    $openPeriod = $openPeriods[$dateRoom->key];
                    $cacheKey = 'OpenPeriod'.'_'.$dateRoom->key;
                    Yii::$app->cache->set($cacheKey, $openPeriod,
                        Yii::$app->params['cache.duration'],
                        new TagDependency(['tags' => ['Room_'.$dateRoom->room_id, 'Room']]));
                    Yii::trace($cacheKey.':写入缓存', '数据缓存'); 
                }
                Yii::endProfile('OpenPeriod写入缓存', '数据缓存');
            }
        }
        return $openPeriods;
    }


    /**
     * 查询所有房间的日期总范围(带缓存)
     * 优先从缓存中查询
     * 
     * @param boolean $useCache 是否使用缓存
     * @return json
     */
    public static function getSumDateRange($now = null, $useCache = true) {
        $sumDateRange;


        $startDate = mktime(0, 0, 0, date("m", $now), date("d", $now), date("Y", $now));
        $endDate = $startDate;
        //$expired = 86400;

        $roomList = RoomService::getRoomList(true, $useCache);
        $dateRanges = RoomService::getDateRanges($roomList, $now);

        foreach ($dateRanges as $dateRange) {
            $endDate = max($endDate, $dateRange['end']);
            //$expired = min($expired,  $dateRange['expired']);
        }
        $sumDateRange = [
            'start' => $startDate,
            'end' => $endDate,
        ];

        return $sumDateRange;
    }

    /**
     * 获取单个RoomTable
     * 如果对应时间表不存在，将会写入一个新的。
     * 调用此方法时不要开事务！
     *
     * @param string $date 预约日期
     * @param integer $room_id 房间id
     * @return 获取单个RoomTable Map形式的Roomtable
     */
    public static function getRoomTable($date, $room_id) {
        $roomTable = RoomTable::findByDateRoom($date, $room_id);
        if ($roomTable === NULL) {
            $dateRoom = new DateRoom($date, $room_id);
            static::addRoomTables([$dateRoom], TRUE, TRUE);
            $roomTable = RoomTable::findByDateRoom($date, $room_id);
        }
        return $roomTable;
    }

    /**
     * 获取房间表
     * 如果对应时间表不存在，将会写入一个新的。在多并发情况下可能会有部分插入失败，但是保证一定有已经插入的存在
     * 调用此方法时不要开事务！
     *
     * @param Array $dateRooms [日期房间]的数组
     * @param $useCache 是否使用缓存(默认为是)
     * @param integer $applyOrder 生成新的房间表时，是否应用预约数据 (默认为是)
     * @param integer $applyLock 生成新的房间表时，是否应用房间锁 (默认为是)
     * @return Array Map形式的Roomtable
     */
    public static function getRoomTables($dateRooms, $useCache = TRUE, 
        $applyOrder = TRUE, $applyLock = TRUE) {
        $roomTables = [];

        //读取缓存
        $cacheMisses;
        if ($useCache) {
            $cacheMisses = [];
            Yii::beginProfile('RoomTable读取缓存', '数据缓存');
            foreach ($dateRooms as $dateRoom) {
                $cacheKey = 'RoomTable'.'_'.$dateRoom->key;
                $cacheData = Yii::$app->cache->get($cacheKey);
                if ($cacheData == null) {
                    Yii::trace($cacheKey.':缓存失效', '数据缓存');
                    $cacheMisses[] = $dateRoom;
                } else {
                    Yii::trace($cacheKey.':缓存命中', '数据缓存');
                    $roomTables[$dateRoom->key] = $cacheData;
                }
            }
            Yii::endProfile('RoomTable读取缓存', '数据缓存');
        } else {
            $cacheMisses = $dateRooms;
        }

        //获取剩下数据(缓存miss的)
        if(count($cacheMisses) > 0) {
            $cacheNews = [];

            //从数据库获取剩余数据
            foreach (RoomTable::findByDateRooms($cacheMisses, ['id', 'date', 'room_id', 'ordered', 'used', 'rejected', 'locked']) as $roomTable) {
                $roomTable['ordered'] = json_decode($roomTable['ordered'], true);
                $roomTable['used'] = json_decode($roomTable['used'], true);
                $roomTable['rejected'] = json_decode($roomTable['rejected'], true);
                $roomTable['locked'] = json_decode($roomTable['locked'], true);
                $roomTable['hourTable'] = RoomTable::getHourTable($roomTable['ordered'], $roomTable['used'], $roomTable['locked']); 
                $dateRoom = new DateRoom($roomTable['date'], $roomTable['room_id']);
                $roomTables[$dateRoom->key] = $roomTable;
                $cacheNews[] = $dateRoom;
            }

            $dbMisses = [];
            foreach ($cacheMisses as $dateRoom) {
                if (!isset($roomTables[$dateRoom->key])) {
                    $dbMisses[] = $dateRoom;
                }
            }
            
            //生成缺失数据(数据库中不存在的)
            if(count($dbMisses) > 0) {
                static::addRoomTables($dbMisses, $applyOrder, $applyLock);
                //从数据库获取新生成的数据
                foreach (RoomTable::findByDateRooms($dbMisses, ['id', 'date', 'room_id', 'ordered', 'used', 'rejected', 'locked']) as $roomTable) {
                    $roomTable['ordered'] = json_decode($roomTable['ordered'], true);
                    $roomTable['used'] = json_decode($roomTable['used'], true);
                    $roomTable['rejected'] = json_decode($roomTable['rejected'], true);
                    $roomTable['locked'] = json_decode($roomTable['locked'], true);
                    $roomTable['hourTable'] = RoomTable::getHourTable($roomTable['ordered'], $roomTable['used'], $roomTable['locked']); 
                    $dateRoom = new DateRoom($roomTable['date'], $roomTable['room_id']);
                    $roomTables[$dateRoom->key] = $roomTable;
                    $cacheNews[] = $dateRoom;
                }
            }

            if ($useCache) {
                //写入缓存
                Yii::beginProfile('RoomTable写入缓存', '数据缓存');
                foreach ($cacheNews as $dateRoom) {
                    $roomTable = $roomTables[$dateRoom->key];
                    $cacheKey = 'RoomTable'.'_'.$dateRoom->key;
                    Yii::$app->cache->set($cacheKey, $roomTable,
                        Yii::$app->params['cache.duration'],
                        new TagDependency(['tags' => [$cacheKey, 'RoomTable']]));
                    Yii::trace($cacheKey.':写入缓存', '数据缓存'); 
                }
                Yii::endProfile('RoomTable写入缓存', '数据缓存');
            }
        }
        return $roomTables;
    }

    /**
     * 批量生成roomtable
     * 如果对应roomtable已经存在，将会更新新的内容
     * 调用此方法时不要开事务！
     *
     * @param Array $dateRooms [日期房间]的数组
     * @param integer $applyOrder 生成新的房间表时，是否应用预约数据 (默认为是)
     * @param integer $applyLock 生成新的房间表时，是否应用房间锁 (默认为是)
     * @return Array Map形式的roomtable
     */
    public static function addRoomTables($dateRooms, $applyOrder = true, $applyLock = true) {
        if ($applyOrder) {
            //批量获取申请
            $orderTables = OrderService::getOrderTables($dateRooms);
        }
        if ($applyLock) {
            //批量获取房间锁
            $lockTables = LockService::getLockTables($dateRooms);
        }

        $roomTableRows = [];
        $roomTables = [];
        $room_ids = Room::getOpenRooms(TRUE);
        foreach ($dateRooms as $dateRoom) {
            if (!in_array($dateRoom->room_id, $room_ids)) {
                continue;
            }
            $roomTable = [
                'date'      => $dateRoom->date,
                'room_id'   => $dateRoom->room_id,
                'ordered'   => [],
                'used'      => [],
                'locked'    => [],
            ];

            if ($applyOrder && isset($orderTables[$dateRoom->key])) {
                //应用申请
                $roomTable['ordered'] = $orderTables[$dateRoom->key]['ordered'];
                $roomTable['used'] = $orderTables[$dateRoom->key]['used'];
                $roomTable['rejected'] = $orderTables[$dateRoom->key]['rejected'];
            };

            
            if ($applyLock && isset($lockTables[$dateRoom->key])) {
                //应用房间锁
                $roomTable['locked'] = $lockTables[$dateRoom->key];
            }

            $roomTables[$dateRoom->key] = $roomTable;

            $roomTableRows[] = [
                $roomTable['date'],
                $roomTable['room_id'],
                json_encode($roomTable['ordered']),
                json_encode($roomTable['used']),
                json_encode($roomTable['rejected']),
                json_encode($roomTable['locked']),
                time(), //created_at
                time(), //updated_at
            ];
        }

        //分组批量插入
        $rows_chunks = array_chunk($roomTableRows, 100);
        foreach ($rows_chunks as $rows_chunk) {
            $sql = Yii::$app->db->getQueryBuilder()->batchInsert(RoomTable::tableName(), 
                ['date', 'room_id', 'ordered', 'used', 'rejected', 'locked', 'created_at','updated_at'],
                $rows_chunk);
            //如果存在重复值，会进行更新
            Yii::$app->db->createCommand(str_replace('INSERT INTO', 'INSERT IGNORE INTO', $sql))->execute();
        }
        return $roomTables;
    }


    /**
     * 应用一个预约
     * 将这个预约信息写入roomTable
     * 该方法将会把对应的roomTable项的id先清空再写入，所以如果$hours为null或者空数组，等同于将该order清除掉
     *
     * @param RoomTable $roomTable roomTable
     * @param id $id 预约id
     * @param array $hours 预约小时数组
     * @param boolean $isUsed true写入used,false写入order
     * @return true 如果写入成功
     * @throws StaleObjectException 如果存在并发冲突
     */
    public static function applyOrder($roomTable, $id, $hours, $isUsed = false) {
        $roomTable->removeOrdered($id);
        $roomTable->removeUsed($id);
        if ($isUsed) {
            $roomTable->addUsed($id, $hours);
        }else{
            $roomTable->addOrdered($id, $hours);
        }
        //清除缓存
        $cache = Yii::$app->cache;
        $cacheKey = RoomTable::getCacheKey($roomTable->date, $roomTable->room_id);
        $cache->delete($cacheKey);

        return $roomTable->save();
    }

     /**
     * 应用房间锁到时间表
     *
     * @param date $start_date 开始时间
     * @param date $end_date 结束时间
     * @return array
     */
    public static function applyLock($start_date, $end_date) {
        $startDateTs = strtotime($start_date);
        $endDateTs = strtotime($end_date);
        $room_ids = Room::getOpenRooms(TRUE);
        $dateRooms = [];

        for ($time = $startDateTs; $time <= $endDateTs; $time = strtotime("+1 day", $time)) {
            $date = date('Y-m-d', $time);
            foreach ($room_ids as $room_id) {
                $dateRoom = new DateRoom($date, $room_id);
                $dateRooms[] = $dateRoom;
            }
        }
        $lockTables = LockService::getLockTables($dateRooms);
        $roomTables = RoomService::getRoomTables($dateRooms, FALSE, TRUE, FALSE);
        $roomTableRows = [];
        $clearCaches = [];
        foreach ($dateRooms as $dateRoom) {
            $lockTable = $lockTables[$dateRoom->key];
            $roomTable = $roomTables[$dateRoom->key];
            if(md5(json_encode($roomTable['locked'])) !== md5(json_encode($lockTable))){
                $roomTable['locked'] = $lockTable;
                $roomTableRows[] = [$roomTable['id'], json_encode($roomTable['locked'])];
                $clearCaches[] = $dateRoom;
            }
        }

        $rows_chunks = array_chunk($roomTableRows, 100);
        foreach ($rows_chunks as $rows_chunk) {
            $sql = Yii::$app->db->getQueryBuilder()->batchInsert(RoomTable::tableName(), ['id','locked'], $rows_chunk);
            Yii::$app->db->createCommand($sql.' ON DUPLICATE KEY UPDATE `locked`=VALUES(`locked`)')->execute();
        }

        //清除缓存
        foreach ($clearCaches as $dateRoom) {
            TagDependency::invalidate(Yii::$app->cache, 'RoomTable_'.$dateRoom->key);
        }
    }

}