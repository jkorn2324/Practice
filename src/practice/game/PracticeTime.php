<?php
/**
 * Created by PhpStorm.
 * User: jkorn2324
 * Date: 2019-05-03
 * Time: 11:20
 */

declare(strict_types=1);

namespace practice\game;

use \DateTime;
use \DateTimeZone;
use practice\PracticeUtil;


class PracticeTime {

    const ONE_YEAR_IN_MIN = 525600;
    const ONE_MONTH_IN_MIN = 43800.048;
    const ONE_DAY_IN_MIN = 1440;
    const ONE_HOUR_IN_MIN = 60;

    private $day;

    private $month;

    private $year;

    private $hour;

    private $minute;

    private $second;

    private $totalMinTime;

    public function __construct() {

        $date = localtime(time(), true);

        $this->year = intval($date['tm_year']) + 1900;

        $this->month = intval($date['tm_mon']) + 1;

        $this->day = intval($date['tm_mday']);

        $this->hour = intval($date['tm_hour']);

        $this->minute = intval($date['tm_min']);

        $this->second = intval($date['tm_sec']);

        $this->totalMinTime = $this->initTotalMinTime();
    }

    private function setTimeValues(int $day, int $month, int $year, int $hour, int $min, int $sec) : PracticeTime{
        $this->year = $year;
        $this->minute = $min;
        $this->second = $sec;
        $this->day = $day;
        $this->month = $month;
        $this->hour = $hour;
        $this->totalMinTime = $this->initTotalMinTime();
        return $this;
    }

    public function add(string $key, int $value) : self {

        switch($key) {
            case 'hr':
                $this->hour += $value;
                break;
            case 'min':
                $this->minute += $value;
                break;
            case 'day':
                $this->minute += $value;
                break;
            case 'mon':
                $this->month += $value;
                break;
            case 'yr':
                $this->year += $value;
                break;
        }

        $this->totalMinTime = $this->initTotalMinTime();

        return $this;
    }

    private function initTotalMinTime() : int {

        return intval(abs(((($this->year - 2018) * self::ONE_YEAR_IN_MIN) + ($this->month * self::ONE_MONTH_IN_MIN) + ($this->day * self::ONE_DAY_IN_MIN) + ($this->hour * self::ONE_HOUR_IN_MIN)) + $this->minute));

    }

    public function getTotalTime() : int {
        return $this->totalMinTime;
    }

    public function getDay() : int {
        return $this->day;
    }

    public function getMonth() : int {
        return $this->month;
    }

    public function getYear() : int {
        return $this->year;
    }

    public function getHour() : int {
        return $this->hour;
    }

    public function getMinute() : int {
        return $this->minute;
    }

    public function getSecond() : int {
        return $this->second;
    }

    public function isInLastMonth(PracticeTime $time) : bool {

        $total = $time->getTotalTime();

        $difference = intval(abs($total - $this->getTotalTime()));

        return (($difference <= self::ONE_MONTH_IN_MIN) and $difference >= 0);
    }

    public function isInLastHour(PracticeTime $time) : bool {

        $total = $time->getTotalTime();

        $difference = intval(abs($total - $this->getTotalTime()));

        return (($difference <= self::ONE_HOUR_IN_MIN) and $difference >= 0);
    }

    public function isInLastDay(PracticeTime $time) : bool {

        $total = $time->getTotalTime();

        $difference = intval(abs($total - $this->getTotalTime()));

        return (($difference <= self::ONE_DAY_IN_MIN) and $difference >= 0);
    }

    public function isInLastYear(PracticeTime $time) : bool {

        $total = $time->getTotalTime();

        $difference = intval(abs($total - $this->getTotalTime()));

        return $difference <= self::ONE_YEAR_IN_MIN and $difference >= 0;
    }

    public function formatTime(bool $file = true) : string {

        $result = '';

        if($file) {

            $hr = $this->formatNum($this->hour);

            $min = $this->formatNum($this->minute);

            $sec = $this->formatNum($this->second);

            $result = "$hr:$min:$sec PST";

        } else {

            $timeOfDay = $this->hour > 11 ? 'pm' : 'am';

            $hr = ($this->hour > 12 or $this->hour === 0) ? intval(abs($this->hour - 12)) : $this->hour;

            $hour = $this->formatNum($hr);

            $min = $this->formatNum($this->minute);

            $sec = $this->formatNum($this->second);

            $result = "$hour:$min:$sec$timeOfDay PST";

        }

        return $result;
    }

    public function formatDate(bool $file = true) : string {

        $result = "";

        if($file) {

            $result = "$this->month/$this->day/$this->year";

        } else {

            $month = $this->monthToString(true);

            $result = "$month $this->day, $this->year";

        }

        return $result;
    }

    private function monthToString(bool $abbreviate = true) : string {

        $result = '';

        switch($this->month) {

            case 1:
                $result = $abbreviate ? 'Jan' : 'January';
                break;
            case 2:
                $result = $abbreviate ? 'Feb' : 'February';
                break;
            case 3:
                $result = $abbreviate ? 'Mar' : 'March';
                break;
            case 4:
                $result = $abbreviate ? 'Apr' : 'April';
                break;
            case 5:
                $result = 'May';
                break;
            case 6:
                $result = $abbreviate ? 'Jun' : 'June';
                break;
            case 7:
                $result = $abbreviate ? 'Jul' : 'July';
                break;
            case 8:
                $result = $abbreviate ? 'Aug' : 'August';
                break;
            case 9:
                $result = $abbreviate ? 'Sept' : 'September';
                break;
            case 10:
                $result = $abbreviate ? 'Oct' : 'October';
                break;
            case 11:
                $result = $abbreviate ? 'Nov' : 'November';
                break;
            case 12:
                $result = $abbreviate ? 'Dec' : 'December';
                break;
        }

        return $result;
    }

    public function toMap() : array {

        $result = [
            'date' => $this->formatDate(),
            'time' => $this->formatTime()
        ];

        return $result;
    }

    public static function parseTime(array $arr) : PracticeTime {

        $result = new PracticeTime();

        if(PracticeUtil::arr_contains_keys($arr, 'date', 'time')) {

            $date = strval($arr['date']);

            $time = strval($arr['time']);

            $splitDate = explode("/", $date);

            $splitTime = explode(':', PracticeUtil::str_replace($time, [' PST' => '']));

            $hours = intval($splitTime[0]);

            $mins = intval($splitTime[1]);

            $secs = intval($splitTime[2]);

            $month = intval($splitDate[0]);

            $day = intval($splitDate[1]);

            $yr = intval($splitDate[2]);

            $result = $result->setTimeValues($day, $month, $yr, $hours, $mins, $secs);
        }

        return $result;
    }

    private function formatNum(int $num) : string {
        $result = "$num";
        if($num < 10) $result = "0$num";
        return $result;
    }

    public function dateForFile(bool $ban = false) : string {
        //05-06-2019 Reports
        $hour = $timeOfDay = $this->hour > 11 ? 'pm' : 'am';
        $extended = ($ban === true) ? 'at ' . $hour : 'Reports';
        return "$this->month-$this->day-$this->year " . $extended;
    }

    public function formatToSql() : string {
        $day = $this->formatNum($this->day);
        $hour = $this->formatNum($this->hour);
        $min = $this->formatNum($this->minute);
        $sec = $this->formatNum($this->second);
        return "$this->year-$this->month-$day $hour:$min:$sec";
    }
}