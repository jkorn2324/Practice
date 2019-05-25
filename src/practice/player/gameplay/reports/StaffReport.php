<?php
/**
 * Created by PhpStorm.
 * User: jkorn2324
 * Date: 2019-05-03
 * Time: 11:09
 */

namespace practice\player\gameplay\reports;


use practice\game\PracticeTime;
use practice\PracticeUtil;

class StaffReport extends AbstractReport {

    private $reportedStaff;

    public function __construct($reporter, $reported, string $description = "", PracticeTime $time = null) {
        parent::__construct($reporter, ReportInfo::REPORT_STAFF, $description, $time);
        $this->reportedStaff = (isset($reported) and !is_null(PracticeUtil::getPlayerName($reported))) ? PracticeUtil::getPlayerName($reported) : parent::STAFF_NONE;
        if(is_null($this->reportedStaff)) $this->reportedStaff = parent::STAFF_NONE;
    }

    public function hasStaff() : bool {
        return $this->reportedStaff !== parent::STAFF_NONE;
    }

    public function getReportedStaff() : string {
        return $this->reportedStaff;
    }

    public function toMap(): array {

        $timeStampArr = $this->time->toMap();

        $reportedType = $this->getReportTypeToStr();

        $reporter = $this->getReporter();
        $reported = $this->getReportedStaff();

        $desc = ($this->description !== "" ? $this->description : parent::STAFF_NONE);

        $info = [
            "reporter" => $reporter,
            "reported" => $reported,
            "reason" => $desc
        ];

        $result = [
            "report-type" => $reportedType,
            "time-stamp" => $timeStampArr,
            "info" => $info
        ];

        return $result;
    }

    public function toMessage(bool $form = true): string {

        $date = $this->time->formatDate(false);
        $time = $this->time->formatTime(false);

        $timeStamp = "$date at $time";

        $reportType = "Staff Report";

        $addedLine = ($form === true) ? "\n" : " ";

        $format = "[$timeStamp]$addedLine$reportType - $this->reporter reported $this->reportedStaff%rest%";

        $rest = "!";

        if($this->hasDescription()) $rest = " for '$this->description.'";

        return PracticeUtil::str_replace($format, ["%rest%" => $rest]);
    }
}