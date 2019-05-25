<?php
/**
 * Created by PhpStorm.
 * User: jkorn2324
 * Date: 2019-05-03
 * Time: 11:08
 */

namespace practice\player\gameplay\reports;


use practice\game\PracticeTime;
use practice\PracticeCore;
use practice\PracticeUtil;

abstract class AbstractReport {

    public const STAFF_NONE = "None";

    protected $time;

    protected $reporter;

    protected $description;

    protected $type;

    public function __construct($reporter, int $type, string $description = "", PracticeTime $time = null) {

        $this->reporter = (isset($reporter) and !is_null(PracticeUtil::getPlayerName($reporter))) ? PracticeUtil::getPlayerName($reporter) : PracticeUtil::genAnonymousName();
        if(is_null($this->reporter)) $this->reporter = PracticeUtil::genAnonymousName();

        $this->description = $description;

        $this->type = $type;

        $this->time = (isset($time) and !is_null($time)) ? $time : new PracticeTime();
    }

    public function getType() : int {
        return $this->type % 3;
    }

    public function getReporter() : string {
        return $this->reporter;
    }

    public function getTime() : PracticeTime {
        return $this->time;
    }

    public function getDescription() : string {
        return $this->description;
    }

    public function hasDescription() : bool {
        return $this->description !== "" and $this->description !== "None";
    }

    protected function getReportTypeToStr(bool $writeFile = true) : string {
        $result = "unknown";
        switch($this->type) {
            case ReportInfo::REPORT_BUG:
                $result = ($writeFile ? "bug-report" : "Bug Report");
                break;
            case ReportInfo::REPORT_HACK:
                $result = ($writeFile ? "hacker-report" : "Hacker Report");
                break;
            case ReportInfo::REPORT_STAFF:
                $result = ($writeFile ? "staff-report" : "Staff Report");
                break;
        }
        return $result;
    }

    public static function getReportTypeFromStr(string $report) : int {
        $result = -1;
        switch($report) {
            case "bug-report":
                $result = ReportInfo::REPORT_BUG;
                break;
            case "hacker-report":
                $result = ReportInfo::REPORT_HACK;
                break;
            case "staff-report":
                $result = ReportInfo::REPORT_STAFF;
                break;
        }
        return $result;
    }

    abstract public function toMap() : array;

    abstract public function toMessage() : string ;

    public static function parseReport(array $reportInfo) {

        $result = null;

        if(PracticeUtil::arr_contains_keys($reportInfo, "report-type", "time-stamp", "info")) {

            $infoData = $reportInfo["info"];

            $timeStamp = $reportInfo["time-stamp"];

            $timeStampObject = PracticeTime::parseTime($timeStamp);

            $reportType = self::getReportTypeFromStr($reportInfo["report-type"]);

            if($reportType === ReportInfo::REPORT_BUG) {

                $reporter = self::STAFF_NONE;

                if(PracticeUtil::arr_contains_keys($reportInfo, "reporter"))
                    $reporter = $reportInfo["reporter"];

                if($reporter !== self::STAFF_NONE and is_array($infoData) and PracticeUtil::arr_contains_keys($infoData, "occurrence", "description")) {

                    $desc = strval($infoData["description"]);

                    $occurrence = strval($infoData["occurrence"]);

                    $result = new BugReport($reporter, $occurrence, $desc, $timeStampObject);
                }

            } elseif ($reportType === ReportInfo::REPORT_HACK) {

                if(is_array($infoData)) {

                    $reporter = strval($infoData["reporter"]);

                    $reported = strval($infoData["reported"]);

                    $desc = strval($infoData["reason"]);

                    $result = new HackReport($reporter, $reported, $desc, $timeStampObject);
                }

            } elseif ($reportType === ReportInfo::REPORT_STAFF) {

                if(is_array($infoData)) {

                    $reporter = strval($infoData["reporter"]);

                    $reported = strval($infoData["reported"]);

                    $desc = strval($infoData["reason"]);

                    $result = new StaffReport($reporter, $reported, $desc, $timeStampObject);
                }
            }
        }

        return $result;
    }
}