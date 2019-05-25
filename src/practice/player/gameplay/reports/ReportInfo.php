<?php
/**
 * Created by PhpStorm.
 * User: jkorn2324
 * Date: 2019-05-07
 * Time: 18:33
 */

declare(strict_types=1);

namespace practice\player\gameplay\reports;

class ReportInfo {

    //TIME CONST

    const LAST_HOUR = 0;

    const LAST_DAY = 1;

    const LAST_MONTH = 2;

    const ALL_TIME = 3;

    //REPORT CONST

    public const REPORT_BUG = 0;

    public const REPORT_HACK = 1;

    public const REPORT_STAFF = 2;

    const ALL_REPORTS = 3;

    public static function getReportName(int $reportType) : string {
        $result = "Reports";
        $reportType = $reportType % 4;
        switch($reportType) {
            case self::REPORT_BUG:
                $result = "Bug-Reports";
                break;
            case self::REPORT_STAFF:
                $result = "Staff-Reports";
                break;
            case self::REPORT_HACK:
                $result = "Hacker-Reports";
                break;
        }
        return $result;
    }

}