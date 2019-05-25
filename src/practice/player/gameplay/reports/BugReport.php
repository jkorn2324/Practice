<?php
/**
 * Created by PhpStorm.
 * User: jkorn2324
 * Date: 2019-05-03
 * Time: 11:09
 */

namespace practice\player\gameplay\reports;


use practice\game\PracticeTime;

class BugReport extends AbstractReport {

    private $occurrence;

    public function __construct($reporter, string $occurrence, string $description = "", PracticeTime $time = null) {
        parent::__construct($reporter, ReportInfo::REPORT_BUG, $description, $time);
        $this->occurrence = $occurrence;
    }

    public function getOccurrence() : string {
        return $this->occurrence;
    }

    public function toMap(): array {

        $timeStampArr = $this->time->toMap();

        $reportedType = $this->getReportTypeToStr();

        $reporter = $this->getReporter();

        $occurs_when = $this->occurrence;

        $desc = ($this->description !== "" ? $this->description : parent::STAFF_NONE);

        $info = [
            "occurrence" => $occurs_when,
            "description" => $desc
        ];

        $result = [
            "report-type" => $reportedType,
            "time-stamp" => $timeStampArr,
            "reporter" => $reporter,
            "info" => $info
        ];

        return $result;
    }

    public function toMessage(bool $form = true): string {

        $reportType = "Bug Report";

        $date = $this->time->formatDate(false);
        $time = $this->time->formatTime(false);

        $timeStamp = "$date at $time";

        $desc = ".";

        if($this->hasDescription()) $desc = " and '" . $this->description . "'.";

        $addedLine = ($form === true) ? "\n" : " ";

        $format = "[$timeStamp]$addedLine$reportType - $this->reporter reported a bug that occurs when '$this->occurrence'$desc";

        return $format;
    }
}