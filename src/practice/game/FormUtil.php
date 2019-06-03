<?php
/**
 * Created by PhpStorm.
 * User: jkorn2324
 * Date: 2019-04-29
 * Time: 16:22
 */

declare(strict_types=1);

namespace practice\game;


use jojoe77777\FormAPI\CustomForm;
use jojoe77777\FormAPI\SimpleForm;
use pocketmine\Player;
use pocketmine\Server;
use pocketmine\utils\TextFormat;
use practice\duels\groups\Request;
use practice\game\items\PracticeItem;
use practice\player\gameplay\reports\AbstractReport;
use practice\player\gameplay\reports\ReportInfo;
use practice\PracticeCore;
use practice\PracticeUtil;

class FormUtil
{

    public static function getMatchForm(bool $ranked = false) : SimpleForm {

        $replace = ($ranked ? 'Ranked' : 'Unranked');

        $form = new SimpleForm(function(Player $event, $data = null) {

            $formData = [];

            $playerHandler = PracticeCore::getPlayerHandler();

            $ivsiHandler = PracticeCore::get1vs1Handler();

            if(PracticeCore::getPlayerHandler()->isPlayerOnline($event))
                $formData = $playerHandler->getPlayer($event)->removeForm();

            if(is_null($data)) {

                if ($ivsiHandler->isLoadingRequest($event)) $ivsiHandler->cancelRequest($event);

            } else {
                if(is_int($data) and $playerHandler->isPlayerOnline($event)) {

                    $p = $playerHandler->getPlayer($event);

                    $buttonIndex = intval($data);

                    $queue = $formData[$buttonIndex]['text'];

                    $index = PracticeUtil::str_indexOf("\n", $queue);

                    if($index !== -1)
                        $queue = substr($queue, 0, $index);

                    $queue = PracticeUtil::getUncoloredString($queue);

                    if(PracticeCore::getKitHandler()->isDuelKit($queue)) PracticeCore::getDuelHandler()->addPlayerToQueue($p->getPlayerName(), $queue, boolval($formData['ranked']));

                } else return;
            }

        });

        $form->setTitle(PracticeUtil::str_replace(PracticeUtil::getMessage('formwindow.duel.title'), ['%ranked%' => $replace]));

        $items = PracticeCore::getItemHandler()->getDuelItems();

        foreach($items as $duelItem) {

            if($duelItem instanceof PracticeItem) {

                $name = $duelItem->getName();

                $uncolored = PracticeUtil::getUncoloredString($name);

                $numInQueue = PracticeCore::getDuelHandler()->getNumQueuedFor($uncolored, $ranked);

                $numInFights = PracticeCore::getDuelHandler()->getNumFightsFor($uncolored, $ranked);

                $inQueues = "\n" . TextFormat::GOLD . '» ' . TextFormat::BLUE . 'In-Queues: ' . TextFormat::WHITE . $numInQueue;

                $inFights = TextFormat::RED . 'In-Fights: ' . TextFormat::WHITE . $numInFights . TextFormat::GOLD . ' «';

                $name .= $inQueues . TextFormat::DARK_GRAY . " | " . $inFights;

                $form->addButton($name, 0, $duelItem->getTexture());
            }
        }

        return $form;
    }

    public static function getDuelsForm() : SimpleForm {

        $form = new SimpleForm(function(Player $event, $data = null) {

            $formData = [];

            $playerHandler = PracticeCore::getPlayerHandler();

            $ivsiHandler = PracticeCore::get1vs1Handler();

            if(PracticeCore::getPlayerHandler()->isPlayerOnline($event))
                $formData = $playerHandler->getPlayer($event)->removeForm();

            if(is_null($data)) {

                if ($ivsiHandler->isLoadingRequest($event)) $ivsiHandler->cancelRequest($event);

            } else {
                if(is_int($data) and $playerHandler->isPlayerOnline($event)) {

                    $p = $playerHandler->getPlayer($event);

                    $buttonIndex = intval($data);

                    $queue = $formData[$buttonIndex]['text'];

                    $queue = PracticeUtil::getUncoloredString($queue);

                    $index = PracticeUtil::str_indexOf("\n", $queue);

                    if($index !== -1)
                        $queue = substr($queue, 0, $index);

                    $queue = PracticeUtil::getUncoloredString($queue);

                    if ($ivsiHandler->isLoadingRequest($event)) {

                        $request = $ivsiHandler->getLoadedRequest($event);

                        $requested = $request->getRequested();

                        if(PracticeCore::getKitHandler()->isDuelKit($queue)) $request->setQueue($queue);

                        if (Request::canSend($p, $requested)) $ivsiHandler->sendRequest($event, $requested);

                        else $ivsiHandler->cancelRequest($request);

                    }
                } else return;
            }

        });

        $form->setTitle(PracticeUtil::getName('title-duel-inventory'));

        $items = PracticeCore::getItemHandler()->getDuelItems();

        foreach($items as $duelItem) {
            if($duelItem instanceof PracticeItem) {
                $name = $duelItem->getName();
                $form->addButton($name, 0, $duelItem->getTexture());
            }
        }

        return $form;
    }


    public static function getFFAForm() : SimpleForm {

        $title = PracticeUtil::getMessage('formwindow.ffa.title');
        $desc = PracticeUtil::getMessage('formwindow.ffa.content');

        $form = new SimpleForm(function(Player $event, $data = null) {

            $formData = [];

            $playerHandler = PracticeCore::getPlayerHandler();

            $arenaHandler = PracticeCore::getArenaHandler();

            if($playerHandler->isPlayerOnline($event))
                $formData = $playerHandler->getPlayer($event)->removeForm();


            if(is_int($data) and $playerHandler->isPlayerOnline($event)) {

                $p = $playerHandler->getPlayer($event);

                $ffaArenaIndex = intval($data);

                $arenaName = $formData[$ffaArenaIndex]['text'];

                $index = PracticeUtil::str_indexOf("\n", $arenaName);

                if($index !== -1)
                    $arenaName = substr($arenaName, 0, $index);

                $arenaName = PracticeUtil::getUncoloredString($arenaName);

                if ($arenaHandler->isFFAArena($arenaName)) {

                    $arena = $arenaHandler->getFFAArena($arenaName);

                    $p->teleportToFFA($arena);
                    //PracticeCore::getInstance()->getScheduler()->scheduleDelayedTask(new TeleportArenaTask($p, $arena), 5);
                }
            }
        });

        $form->setTitle($title);
        $form->setContent($desc);

        $itemHandler = PracticeCore::getItemHandler();

        $items = $itemHandler->getFFAItems();

        foreach($items as $item) {

            if($item instanceof PracticeItem) {

                $name = $item->getName();
                $numPlayers = PracticeCore::getArenaHandler()->getNumPlayersInArena($name);
                $name .= "\n" . TextFormat::RED . 'Players: ' . TextFormat::WHITE . $numPlayers;

                $texture = $item->getTexture();
                $form->addButton($name, 0, $texture);
            }
        }

        return $form;
    }

    public static function getReportBugForm() : CustomForm {

        $title = 'Report a Bug';

        $occurrenceContent = 'Describe WHEN the bug occurs and HOW to recreate it:';

        $descriptionContent = 'Describe WHAT happens when the bug occurs:';

        $form = new CustomForm(function(Player $event, $data = null){

            if(PracticeCore::getPlayerHandler()->isPlayerOnline($event))
                PracticeCore::getPlayerHandler()->getPlayer($event)->removeForm();

            if(!is_null($data) and is_array($data)) {

                $occurrenceIndex = 0;
                $descIndex = 1;

                $occurrence = strval($data[$occurrenceIndex]);
                $desc = strval($data[$descIndex]);

                PracticeCore::getReportHandler()->createBugReport($event->getName(), $occurrence, $desc);

                $msg = 'Successfully reported a bug. It will get fixed soon!';

                $event->sendMessage($msg);
            }
        });

        $form->setTitle($title);

        $form->addInput($occurrenceContent);

        $form->addInput($descriptionContent);

        return $form;
    }

    public static function getReportHackForm(string $excludedName = '') : CustomForm {

        $title = 'Report a Player!';

        $reportedPlayerContent = 'Select the player to report:';

        $descriptionContent = 'Describe the reasons for reporting this player:';

        $form = new CustomForm(function(Player $event, $data = null) {

            $formData = [];

            if(PracticeCore::getPlayerHandler()->isPlayerOnline($event))
                $formData = PracticeCore::getPlayerHandler()->getPlayer($event)->removeForm();

            if(!is_null($data) and is_array($data)) {

                $dropdownIndex = 0;

                $reasonIndex = 1;

                $reason = strval($data[$reasonIndex]);

                $reportedIndex = $data[$dropdownIndex];

                if(count($formData[$dropdownIndex]['options']) > 0) {

                    $reported = strval($formData[$dropdownIndex]['options'][$reportedIndex]);

                    PracticeCore::getReportHandler()->createStaffReport($event->getName(), $reported, $reason);

                    $event->sendMessage(PracticeUtil::str_replace(PracticeUtil::getMessage('general.report.success'), ['%player%' => $reported]));

                }
            }
        });

        $form->setTitle($title);

        $dropdownOptions = [];

        $onlinePlayers = Server::getInstance()->getOnlinePlayers();

        foreach($onlinePlayers as $player) {
            if($player->getName() !== $excludedName) {
                $dropdownOptions[] = $player->getName();
            }
        }

        $form->addDropdown($reportedPlayerContent, $dropdownOptions);

        $form->addInput($descriptionContent);

        return $form;
    }

    public static function getReportStaffForm(string $excludedName = '') : CustomForm {

        $title = 'Report a staff member!';

        $reportedStaffContent = 'Select the staff member to report!';

        $descriptionContent = 'Describe the reasons for reporting this staff member:';

        $form = new CustomForm(function(Player $event, $data = null) {

            $formData = [];

            if(PracticeCore::getPlayerHandler()->isPlayerOnline($event))
                $formData = PracticeCore::getPlayerHandler()->getPlayer($event)->removeForm();

            if(!is_null($data) and is_array($data)) {

                $dropdownIndex = 0;

                $reasonIndex = 1;

                $resReportedIndex = $data[$dropdownIndex];

                if(count($formData[$dropdownIndex]['options']) > 0) {

                    $reported = strval($formData[$dropdownIndex]['options'][$resReportedIndex]);

                    $reason = strval($data[$reasonIndex]);

                    PracticeCore::getReportHandler()->createStaffReport($event->getName(), $reported, $reason);

                    $event->sendMessage(PracticeUtil::str_replace(PracticeUtil::getMessage('general.report.success'), ['%player%' => $reported]));

                }
            }
        });

        $form->setTitle($title);

        $dropdownOptions = [];

        $staffMembers = PracticeCore::getPlayerHandler()->getOnlineStaff();

        foreach($staffMembers as $player) {
            $name = strval($player);
            if($excludedName !== $name) $dropdownOptions[] = $name;
        }

        $form->addDropdown($reportedStaffContent, $staffMembers);

        $form->addInput($descriptionContent);

        return $form;
    }

    public static function getReportsForm(int $timeFrame = ReportInfo::ALL_TIME, int $reportType = ReportInfo::ALL_REPORTS) : CustomForm {

        $form = new CustomForm(function(Player $event, $data = null) {

            if(PracticeCore::getPlayerHandler()->isPlayer($event))
                PracticeCore::getPlayerHandler()->getPlayer($event)->removeForm();

            if(!is_null($data) and is_array($data)) {

                $timeIndex = 0;
                $typeIndex = 1;

                $timeFrame = intval($data[$timeIndex]);
                $reportType = intval($data[$typeIndex]);

                $form = self::getReportsForm($timeFrame, $reportType);

                if(PracticeCore::getPlayerHandler()->isPlayer($event)) {
                    $p = PracticeCore::getPlayerHandler()->getPlayer($event);
                    $p->sendForm($form);
                }
            }
        });

        $title = 'All %name%';

        switch($timeFrame) {
            case ReportInfo::LAST_DAY:
                $title = '%name% from the Past Day';
                break;
            case ReportInfo::LAST_HOUR:
                $title = '%name% from the Past Hour';
                break;
            case ReportInfo::LAST_MONTH:
                $title = '%name% from the Past Month';
                break;
        }

        $title = PracticeUtil::str_replace($title, ['%name%' => ReportInfo::getReportName($reportType)]);

        $form->setTitle($title);

        $form->addStepSlider('By TimeFrame: ', array('Past-hour', 'Past-day', 'Past-month', 'All-time'), $timeFrame);

        $form->addStepSlider('By ReportType: ', array(ReportInfo::getReportName(ReportInfo::REPORT_BUG), ReportInfo::getReportName(ReportInfo::REPORT_HACK), ReportInfo::getReportName(ReportInfo::REPORT_STAFF), 'All-Reports'), $reportType);

        $reportArray = PracticeCore::getReportHandler()->getReports($timeFrame, $reportType);

        $count = count($reportArray);

        if($count > 0) {

            $form->addLabel('List of Reports:');

            foreach ($reportArray as $report) {

                if ($report instanceof AbstractReport) {

                    $label = $report->toMessage();

                    $form->addLabel($label);
                }
            }
        } else $form->addLabel('There are no reports');

        return $form;
    }

    public static function getStatsForm(string $player) : CustomForm {

        $stats = PracticeCore::getPlayerHandler()->getStats($player);

        $form = new CustomForm(function(Player $event, $data = null){

            if(PracticeCore::getPlayerHandler()->isPlayer($event))
                PracticeCore::getPlayerHandler()->getPlayer($event)->removeForm();

        });

        $form->setTitle($stats['title']);

        $form->addLabel($stats['kills']);

        $form->addLabel($stats['deaths']);

        $form->addLabel($stats['elo']);

        return $form;
    }

    public static function getSettingsForm(string $player, bool $op = false) : CustomForm {

        $form = new CustomForm(function(Player $event, $data = null){

            if(PracticeCore::getPlayerHandler()->isPlayer($event))
                PracticeCore::getPlayerHandler()->getPlayer($event)->removeForm();

            if(!is_null($data) and is_array($data)) {

                $dataSBIndex = 0;

                $resultSB = boolval($data[$dataSBIndex]);

                if (PracticeCore::getPlayerHandler()->isPlayerOnline($event)) {

                    $p = PracticeCore::getPlayerHandler()->getPlayer($event);

                    if ($resultSB === true) {

                        if (!PracticeCore::getPlayerHandler()->isScoreboardEnabled($event->getName()));
                            $p->showScoreboard();

                    } else {

                        if (PracticeCore::getPlayerHandler()->isScoreboardEnabled($event->getName()));
                            $p->hideScoreboard();
                    }

                    PracticeCore::getPlayerHandler()->enableScoreboard($event->getName(), $resultSB);

                    $dataPEOnlyIndex = 1;

                    if(isset($data[$dataPEOnlyIndex])) {

                        $resultPEOnly = boolval($data[$dataPEOnlyIndex]);

                        PracticeCore::getPlayerHandler()->setPEOnlySetting($event->getName(), $resultPEOnly);
                    }

                    $dataPBIndex = 2;

                    if(isset($data[$dataPBIndex])) {

                        $resultPB = boolval($data[$dataPBIndex]);

                        PracticeCore::getPlayerHandler()->setPlaceNBreak($event->getName(), $resultPB);

                    }
                }
            }
        });

        $form->setTitle('Your Settings');

        $form->addToggle('Enable Scoreboard', PracticeCore::getPlayerHandler()->isScoreboardEnabled($player));

        $changePEOnlySettings = false;

        if(PracticeCore::getPlayerHandler()->isPlayerOnline($player)) {
            $p = PracticeCore::getPlayerHandler()->getPlayer($player);
            $changePEOnlySettings = $p->peOnlyQueue();
        }

        if($changePEOnlySettings === true) $form->addToggle('Enable PE Only Queues', PracticeCore::getPlayerHandler()->canQueuePEOnly($player));
        else $form->addLabel('Enable PE Only Queues:' . "\n" . TextFormat::RED . "Windows 10/Controller players can't change this setting.");

        if($op === true) $form->addToggle('Enable Placing and Breaking Blocks', PracticeCore::getPlayerHandler()->canPlaceNBreak($player));

        return $form;
    }

    public static function createPartyForm() : CustomForm {

        $form = new CustomForm(function(Player $event, $data = null){

            if(PracticeCore::getPlayerHandler()->isPlayerOnline($event))
                PracticeCore::getPlayerHandler()->getPlayer($event)->removeForm();

            if(!is_null($data) and is_array($data)) {

                $dataNameIndex = 0;

                $resultName = strval($data[$dataNameIndex]);

                PracticeCore::getPartyManager()->createParty($event, $resultName);
            }
        });

        $form->setTitle('Create a Party!');

        $form->addInput('Provide a name for your party:', 'MyParty');

        return $form;
    }

    //TODO
    public static function getPartySettingsForm() : CustomForm {

        $form = new CustomForm(function(Player $event, $data = null) {

        });

        return $form;
    }
}