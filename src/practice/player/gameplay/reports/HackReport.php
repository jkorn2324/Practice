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

class HackReport extends AbstractReport {
    private $reportedPlayer;

    public function __construct($reporter, $reported, string $description = "", PracticeTime $time = null) {
        parent::__construct($reporter, ReportInfo::REPORT_HACK, $description, $time);
        $this->reportedPlayer = (isset($reported) and !is_null(PracticeUtil::getPlayerName($reported))) ? PracticeUtil::getPlayerName($reported) : parent::STAFF_NONE;
        if(is_null($this->reportedPlayer)) $this->reportedPlayer = parent::STAFF_NONE;
    }

    public function isReportedPlayerValid() : bool {
        return $this->reportedPlayer !== self::STAFF_NONE;
    }

    public function getReportedPlayer() : string {
        return $this->reportedPlayer;
    }

    public function toMap(): array {

        $timeStampArr = $this->time->toMap();

        $reportedType = $this->getReportTypeToStr();

        $reporter = $this->getReporter();
        $reported = $this->getReportedPlayer();

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

        $reportType = "Hacker Report";

        $date = $this->time->formatDate(false);
        $time = $this->time->formatTime(false);

        $timeStamp = "$date at $time";

        $desc = "!";

        if($this->hasDescription()) $desc = " for '$this->description.'";

        $addedLine = ($form === true) ? "\n" : " ";

        $format = "[$timeStamp]$addedLine$reportType - $this->reporter reported $this->reportedPlayer$desc";

        return $format;
    }
}