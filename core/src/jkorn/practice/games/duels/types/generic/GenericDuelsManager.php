<?php

declare(strict_types=1);

namespace jkorn\practice\games\duels\types\generic;


use jkorn\practice\arenas\types\duels\DuelArenaManager;
use jkorn\practice\arenas\types\duels\IDuelArena;
use jkorn\practice\arenas\types\duels\PostGeneratedDuelArena;
use jkorn\practice\games\IGame;
use jkorn\practice\games\misc\IAwaitingGameManager;
use jkorn\practice\games\misc\IAwaitingManager;
use jkorn\practice\kits\IKit;
use jkorn\practice\level\gen\arenas\duels\DuelGeneratorInfo;
use jkorn\practice\level\gen\PracticeGeneratorInfo;
use jkorn\practice\level\gen\PracticeGeneratorManager;
use jkorn\practice\player\info\settings\properties\BooleanSettingProperty;
use jkorn\practice\player\info\settings\SettingsInfo;
use jkorn\practice\player\PracticePlayer;
use pocketmine\Player;
use pocketmine\Server;
use jkorn\practice\PracticeCore;

class GenericDuelsManager implements IAwaitingGameManager
{

    // Constant for PE Only Queues.
    const SETTING_PE_ONLY = "pe.only";

    /** @var int - The ids of the generic duels. */
    private static $genericDuelIDs = 0;

    /** @var Server */
    private $server;
    /** @var PracticeCore */
    private $core;

    /** @var IGenericDuel[] */
    private $duels;
    /** @var GenericQueuesManager */
    private $queuesManager;

    public function __construct(PracticeCore $core)
    {
        $this->core = $core;
        $this->server = $core->getServer();
        $this->duels = [];

        $this->queuesManager = new GenericQueuesManager($this);
    }

    /**
     * Updates the games in the game manager.
     * @param int $currentTick
     */
    public function update(int $currentTick): void
    {
        foreach ($this->duels as $duel) {
            $duel->update();
        }
    }

    /**
     * @param mixed ...$args - The arguments needed to create a new game.
     *
     * The arguments needed to create a new
     */
    public function create(...$args): void
    {
        if (count($args) !== 3) {
            return;
        }

        $duelID = self::$genericDuelIDs++;

        /** @var PracticePlayer[] $players */
        $players = $args[0];
        /** @var IKit $kit */
        $kit = $args[1];
        /** @var bool $found */
        $found = $args[2];

        // Generates a random arena.
        $randomArena = $this->randomArena($duelID, count($players));

        if (count($players) !== 2) {
            $duel = new GenericTeamDuel($duelID, $kit, $randomArena, ...$players);
        } else {
            $duel = new Generic1vs1($duelID, $kit, $randomArena, $players[0], $players[1]);
        }

        $this->duels[$duel->getID()] = $duel;

        if ($found) {
            // TODO: Send message
        } else {

        }
    }

    /**
     * @param $game
     *
     * Removes the duel from the manager.
     */
    public function remove($game): void
    {
        if ($game instanceof IGenericDuel && isset($this->duels[$game->getID()])) {
            unset($this->duels[$game->getID()]);
        }
    }

    /**
     * @return string
     *
     * Gets the type of game manager.
     */
    public function getType(): string
    {
        return self::MANAGER_GENERIC_DUELS;
    }

    /**
     * @return string
     *
     * Gets the title of the type of game.
     */
    public function getTitle(): string
    {
        return "Generic Duels";
    }

    /**
     * @return string
     *
     * Gets the texture of the game type, used for forms.
     */
    public function getTexture(): string
    {
        return "textures/ui/fire_resistance_effect.png";
    }

    /**
     * Called when the game manager is first registered.
     */
    public function onRegistered(): void
    {
        // TODO: Register the statistics

        // Registers the PE Only Queues Setting.
        SettingsInfo::registerSetting(self::SETTING_PE_ONLY, BooleanSettingProperty::class,
            false, [
                "enabled" => "Enable PE-Only Duels",
                "disabled" => "Disable PE-Only Duels"
            ]);

        PracticeCore::getBaseArenaManager()->registerArenaManager(
            new DuelArenaManager($this->core),
            true
        );
    }

    /**
     * Called when the game manager is unregistered.
     */
    public function onUnregistered(): void
    {
        // TODO: Unregister the statistics.
        // TODO: Unregister the Post-Duel Arena Manager

        // Unregisters the PE Only Queues Setting.
        SettingsInfo::unregisterSetting(self::SETTING_PE_ONLY);
    }

    /**
     * @param int $duelID - The ID of the duel, used for if there aren't
     *              any duel arenas available, there are ways to generate
     *              a new arena.
     * @param int $numPlayers - Gets the number of players in the duel.
     *
     * @return IDuelArena
     *
     * Generates a random duel arena.
     */
    protected function randomArena(int $duelID, int $numPlayers): IDuelArena
    {
        $duelArenaManager = PracticeCore::getBaseArenaManager()->getArenaManager("duels");
        if($duelArenaManager instanceof DuelArenaManager)
        {
            $duelArena = $duelArenaManager->randomArena();
        }

        if(!isset($duelArena) || $duelArena === null)
        {
            $levelName = "game.duels.generic.{$duelID}";
            /** @var DuelGeneratorInfo $randomGenerator */
            $randomGenerator = PracticeGeneratorManager::randomGenerator(
                function(PracticeGeneratorInfo $info) use($numPlayers)
                {
                    $type = $numPlayers === 2 ? DuelGeneratorInfo::TYPE_1VS1 : DuelGeneratorInfo::TYPE_TEAM;
                    return $info instanceof DuelGeneratorInfo
                        && ($info->getType() === DuelGeneratorInfo::TYPE_ANY
                            || $info->getType() === $type);
                }
            );
            $this->server->generateLevel($levelName, null, $randomGenerator->getClass());
            $this->server->loadLevel($levelName);
            $duelArena = new PostGeneratedDuelArena($levelName, $randomGenerator);
        }

        return $duelArena;
    }

    /**
     * @param Player $player
     * @return IGame|null
     *
     * Gets the game from the player.
     */
    public function getFromPlayer(Player $player): ?IGame
    {
        foreach ($this->duels as $duel) {
            if ($duel->isPlaying($player)) {
                return $duel;
            }
        }

        return null;
    }

    /**
     * @return int
     *
     * Gets the number of players playing.
     */
    public function getPlayersPlaying(): int
    {
        $numberOfPlayers = 0;
        foreach ($this->duels as $duel) {
            $numberOfPlayers += $duel->getNumberOfPlayers();
        }
        return $numberOfPlayers;
    }

    /**
     * @param $manager
     * @return bool
     *
     * Determines if one manager is equivalent to another.
     */
    public function equals($manager): bool
    {
        return is_a($manager, __NAMESPACE__ . "\\" . self::class)
            && get_class($manager) === self::class;
    }

    /**
     * @return IAwaitingManager
     *
     * Gets the awaiting manager, in this case its the queues manager.
     */
    public function getAwaitingManager(): IAwaitingManager
    {
        return $this->queuesManager;
    }
}