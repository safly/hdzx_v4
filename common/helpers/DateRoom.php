<?php
/**
 * @link http://www.j3l11234.com/
 * @copyright Copyright (c) 2015 j3l11234
 * @author j3l11234@j3l11234.com
 */

namespace common\helpers;

/**
 * Date Room 日期房间二元组
 */

class DateRoom { 
    public $date, $room_id;

    public function __construct($date, $room_id) {
        $this->date = $date;
        $this->room_id = $room_id;
    }

    public function __get($name) {
        if ($name === 'key') {
            return $this->__toString();
        }
    }

    public function __toString() {
        return "{$this->date}_{$this->room_id}";
    }

    public function toArray() {
        return [
            'date' => $this->date,
            'room_id' => $this->room_id
        ];
    }
}