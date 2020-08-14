<?php

declare(strict_types=1);

namespace jkorn\ffa;


use jkorn\ffa\arenas\FFAArena;
use jkorn\ffa\arenas\FFAArenaManager;
use jkorn\ffa\forms\FFAFormManager;
use jkorn\ffa\games\FFAGame;
use jkorn\ffa\scoreboards\FFAScoreboardManager;
use jkorn\ffa\statistics\FFADisplayStatistics;
use jkorn\practice\arenas\PracticeArenaManager;
use jkorn\practice\display\DisplayStatistic;
use jkorn\practice\forms\display\FormDisplay;
use jkorn\practice\forms\types\properties\ButtonTexture;
use jkorn\practice\games\misc\gametypes\IGame;
use jkorn\practice\games\misc\leaderboards\IGameLeaderboard;
use jkorn\practice\games\misc\managers\IGameManager;
use jkorn\practice\kits\IKit;
use jkorn\practice\player\info\stats\properties\IntegerStatProperty;
use jkorn\practice\player\info\stats\StatPropertyInfo;
use jkorn\practice\player\info\stats\StatsInfo;
use jkorn\practice\player\PracticePlayer;
use jkorn\practice\PracticeCore;
use pocketmine\Player;
use pocketmine\Server;

class FFAGameManager implements IGameManager, FFADisplayStatistics
{

    const GAME_TYPE = "ffa.game.manager";

    /** @var FFAGame[] */
    private $games;

    /** @var FFAAddon */
    private $core;
    /** @var Server */
    private $server;

    /** @var FFAArenaManager */
    private $arenaManager;

    public function __construct(FFAAddon $core)
    {
        $this->games = [];
        $this->core = $core;
        $this->server = $core->getServer();

        $this->arenaManager = new FFAArenaManager($core, $this);
    }


    /**
     * Called when the game manager is first registered.
     */
    public function onRegistered(): void
    {
        // Loads the arena manager.
        $this->arenaManager->load();

        // Registers the arena manager.
        PracticeCore::getBaseScoreboardDisplayManager()->registerScoreboardManager(
            new FFAScoreboardManager($this->core), true);
        PracticeCore::getBaseFormDisplayManager()->registerFormDisplayManager(
            new FFAFormManager($this->core), true);
        // TODO: Message manager.
        /* PracticeCore::getBaseMessageManager()->register(
            new FFAMessageManager($this->core), true); */

        // Registers the ffa player kills statistic.
        StatsInfo::registerStatistic(new StatPropertyInfo(
            self::STATISTIC_FFA_PLAYER_KILLS,
            IntegerStatProperty::class,
            true,
            0
        ));

        // Registers the ffa player deaths statistic.
        StatsInfo::registerStatistic(new StatPropertyInfo(
            self::STATISTIC_FFA_PLAYER_DEATHS,
            IntegerStatProperty::class,
            true,
            0
        ));

        // Registers how many players are playing in an ffa arena.
        DisplayStatistic::register(new DisplayStatistic(
            self::STATISTIC_FFA_ARENA_PLAYERS_PLAYING,
            function (Player $player, Server $server, $data) {
                if ($data instanceof FFAGame) {
                    return $data->getPlayersPlaying();
                } elseif ($player instanceof PracticePlayer) {
                    $game = $player->getCurrentGame();
                    if($game instanceof FFAGame)
                    {
                        return $game->getPlayersPlaying();
                    }
                }
                return 0;
            }
        ));

        // Registers how many players are playing in all ffa arenas.
        DisplayStatistic::register(new DisplayStatistic(
            self::STATISTIC_FFA_PLAYERS_PLAYING,
            function (Player $player, Server $server, $data) {

                $manager = PracticeCore::getBaseGameManager()->getGameManager(self::GAME_TYPE);
                if($manager === null)
                {
                    return 0;
                }
                return $manager->getPlayersPlaying();
            }
        ));

        // Gets the FFA arena name statistic.
        DisplayStatistic::register(new DisplayStatistic(
            self::STATISTIC_FFA_ARENA_NAME,
            function (Player $player, Server $server, $data) {
                if ($data instanceof FFAGame) {
                    $arena = $data->getArena();
                    if($arena !== null)
                    {
                        return $arena->getName();
                    }
                } elseif ($data instanceof FFAArena) {
                    return $data->getName();
                } elseif ($player instanceof PracticePlayer) {
                    $game = $player->getCurrentGame();
                    if($game instanceof FFAGame)
                    {
                        $arena = $game->getArena();
                        if($arena !== null)
                        {
                            return $arena->getName();
                        }
                    }
                }

                return "Unknown";
            }
        ));

        // Gets the FFA Arena kit statistic.
        DisplayStatistic::register(new DisplayStatistic(
            self::STATISTIC_FFA_ARENA_KIT,
            function (Player $player, Server $server, $data) {
                if($data instanceof FFAGame)
                {
                    $arena = $data->getArena();
                    if($arena !== null)
                    {
                        $kit = $arena->getKit();
                        if($kit !== null)
                        {
                            return $kit->getName();
                        }
                    }
                    return "None";
                } elseif ($data instanceof FFAArena) {
                    $kit = $data->getKit();
                    if ($kit !== null) {
                        return $kit->getName();
                    }
                    return "None";
                } elseif ($data instanceof IKit) {
                    return $data->getName();
                } elseif ($player instanceof PracticePlayer) {
                    $game = $player->getCurrentGame();
                    if($game instanceof FFAGame)
                    {
                        $arena = $game->getArena();
                        if($arena !== null)
                        {
                            $kit = $arena->getKit();
                            if($kit !== null)
                            {
                                return $kit->getName();
                            }
                        }
                    }
                }
                return "Unknown";
            }
            , false));
    }

    /**
     * Called when the game manager is unregistered.
     */
    public function onUnregistered(): void
    {
        StatsInfo::unregisterStatistic(self::STATISTIC_FFA_PLAYER_KILLS);
        StatsInfo::unregisterStatistic(self::STATISTIC_FFA_PLAYER_DEATHS);

        DisplayStatistic::unregisterStatistic(self::STATISTIC_FFA_ARENA_PLAYERS_PLAYING);
        DisplayStatistic::unregisterStatistic(self::STATISTIC_FFA_PLAYERS_PLAYING);
        DisplayStatistic::unregisterStatistic(self::STATISTIC_FFA_ARENA_NAME);
        DisplayStatistic::unregisterStatistic(self::STATISTIC_FFA_ARENA_KIT);
    }

    /**
     * Called when the game manager has been saved.
     */
    public function onSave(): void
    {
        $this->arenaManager->save();
    }

    /**
     * @param Player $player
     * @return IGame|null - Returns the game the player is playing, false otherwise.
     *
     * Gets the game from the player.
     */
    public function getFromPlayer(Player $player): ?IGame
    {
        foreach($this->games as $game)
        {
            if($game->isPlaying($player))
            {
                return $game;
            }
        }

        return null;
    }

    /**
     * @param $arena - The arena for the game.
     *
     * Used to add a game to the game list,
     * should not be used by external plugins.
     */
    public function createGame(&$arena): void
    {
        if(!$arena instanceof FFAArena)
        {
            return;
        }

        $game = new FFAGame($arena);
        $this->games[$arena->getLocalizedName()] = $game;
    }

    /**
     * @param string $localized
     *
     * Removes the game from the ffa game manager,
     * should not be used by external addons or plugins.
     */
    public function removeGame(string $localized): void
    {
        if(isset($this->games[$localized]))
        {
            // TODO: Remove players from game possibly.
            unset($this->games[$localized]);
        }
    }

    /**
     * @param callable|null $filter - The callable to filter out the games.
     *
     * @return FFAGame[]
     *
     * Gets the games based on the filter.
     */
    public function getGames(?callable $filter = null)
    {
        if($filter !== null)
        {
            return array_filter($this->games, $filter);
        }

        return $this->games;
    }

    /**
     * @return string
     *
     * Gets the type of game manager.
     */
    public function getType(): string
    {
        return self::GAME_TYPE;
    }

    /**
     * @return string
     *
     * Gets the title of the type of game.
     */
    public function getDisplayName(): string
    {
        return "FFA";
    }

    /**
     * @return ButtonTexture|null
     *
     * Gets the form button texture.
     */
    public function getFormButtonTexture(): ?ButtonTexture
    {
        return new ButtonTexture(ButtonTexture::TYPE_PATH, "textures/ui/icon_recipe_equipment.png");
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
     * @return int
     *
     * Gets the number of players playing.
     */
    public function getPlayersPlaying(): int
    {
        if(count($this->games) <= 0)
        {
            return 0;
        }

        $playersPlaying = 0;

        foreach($this->games as $game)
        {
            $playersPlaying += $game->getPlayersPlaying();
        }

        return $playersPlaying;
    }

    /**
     * @param Player $player - The player that selected it.
     *
     * Called when the game is selected in the Play Games Form.
     */
    public function onGameSelected(Player $player): void
    {
        $formManager = PracticeCore::getBaseFormDisplayManager()->getFormManager(FFAFormManager::LOCALIZED_NAME);
        if($formManager !== null)
        {
            $form = $formManager->getForm(FFAFormManager::FFA_PLAY_FORM);
            if($form !== null)
            {
                $form->display($player);
            }
        }
    }

    /**
     * @return IGameLeaderboard|null
     *
     * Gets the leaderboard of the game manager. Return null
     * if the game doesn't have a leaderboard.
     */
    public function getLeaderboard(): ?IGameLeaderboard
    {
        return null;
    }

    /**
     * @return PracticeArenaManager
     *
     * Gets the game's arena manager.
     */
    public function getArenaManager(): PracticeArenaManager
    {
        return $this->arenaManager;
    }
}