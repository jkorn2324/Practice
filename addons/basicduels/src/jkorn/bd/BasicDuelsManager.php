<?php

declare(strict_types=1);

namespace jkorn\bd;


use jkorn\bd\arenas\IDuelArena;
use jkorn\bd\arenas\ArenaManager;
use jkorn\bd\arenas\PostGeneratedDuelArena;
use jkorn\bd\duels\Basic1vs1;
use jkorn\bd\duels\leaderboard\BasicDuelsLeaderboards;
use jkorn\bd\duels\BasicTeamDuel;
use jkorn\bd\duels\types\BasicDuelGameInfo;
use jkorn\bd\forms\BasicDuelsFormManager;
use jkorn\bd\gen\BasicDuelsGeneratorInfo;
use jkorn\bd\duels\IBasicDuel;
use jkorn\bd\messages\BasicDuelsMessageManager;
use jkorn\bd\queues\BasicQueuesManager;
use jkorn\bd\scoreboards\BasicDuelsScoreboardManager;
use jkorn\practice\arenas\PracticeArenaManager;
use jkorn\practice\forms\types\properties\ButtonTexture;
use jkorn\practice\games\duels\AbstractDuel;
use jkorn\practice\games\misc\gametypes\IGame;
use jkorn\practice\games\misc\gametypes\ISpectatorGame;
use jkorn\practice\games\misc\managers\IAwaitingGameManager;
use jkorn\practice\games\misc\managers\awaiting\IAwaitingManager;
use jkorn\practice\games\misc\leaderboards\IGameLeaderboard;
use jkorn\practice\games\misc\managers\ISpectatingGameManager;
use jkorn\practice\games\misc\managers\IUpdatedGameManager;
use jkorn\practice\kits\IKit;
use jkorn\practice\level\gen\PracticeGeneratorInfo;
use jkorn\practice\level\gen\PracticeGeneratorManager;
use jkorn\practice\player\PracticePlayer;
use jkorn\practice\PracticeUtil;
use pocketmine\Player;
use pocketmine\Server;
use jkorn\practice\PracticeCore;

class BasicDuelsManager implements IAwaitingGameManager, ISpectatingGameManager, IUpdatedGameManager
{

    const NAME = "basic.duels";

    /** @var int - The ids of the generic duels. */
    private static $genericDuelIDs = 0;

    /** @var Server */
    private $server;

    /** @var [] */
    private $gameTypes = [];

    /** @var IBasicDuel[] */
    private $duels;
    /** @var BasicQueuesManager */
    private $queuesManager;
    /** @var BasicDuelsLeaderboards */
    private $leaderboards;

    /** @var ArenaManager */
    private $arenaManager;

    /** @var BasicDuels */
    private $core;

    public function __construct(BasicDuels $core)
    {
        $this->server = PracticeCore::getInstance()->getServer();
        $this->core = $core;

        $this->duels = [];

        $this->initGameTypes();

        $this->arenaManager = new ArenaManager($core, $this);
        $this->queuesManager = new BasicQueuesManager($this);
        $this->leaderboards = new BasicDuelsLeaderboards($this);
    }

    /**
     * Initializes the game types.
     */
    private function initGameTypes(): void
    {
        $this->registerGameType(new BasicDuelGameInfo(2, "1vs1",
            new ButtonTexture(ButtonTexture::TYPE_PATH, "textures/ui/dressing_room_customization.png")));
        $this->registerGameType(new BasicDuelGameInfo(4, "2vs2",
            new ButtonTexture(ButtonTexture::TYPE_PATH, "textures/ui/FriendsDiversity.png")));
        $this->registerGameType(new BasicDuelGameInfo(6, "3vs3",
            new ButtonTexture(ButtonTexture::TYPE_PATH, "textures/ui/dressing_room_skins.png")));
    }

    /**
     * @param BasicDuelGameInfo $gameType
     *
     * Registers the game type.
     */
    private function registerGameType(BasicDuelGameInfo $gameType): void
    {
        $this->gameTypes[$gameType->getLocalizedName()] = $gameType;
    }

    /**
     * @param string $gameType
     * @return BasicDuelGameInfo|null
     *
     * Gets the game type based on the localized name.
     */
    public function getGameType(string $gameType): ?BasicDuelGameInfo
    {
        if(isset($this->gameTypes[$gameType]))
        {
            return $this->gameTypes[$gameType];
        }

        return null;
    }


    /**
     * Updates the games in the game manager.
     * @param int $currentTick
     */
    public function update(int $currentTick): void
    {
        foreach ($this->duels as $duel)
        {
            $duel->update();
        }

        if($currentTick % (BasicDuelsLeaderboards::LEADERBOARD_UPDATE_SECONDS * 20) === 0)
        {
            $this->leaderboards->update();
        }
    }

    /**
     * @param mixed ...$args - The arguments needed to create a new game.
     *
     * The arguments needed to create a new game.
     */
    public function create(...$args): void
    {
        if (count($args) !== 4) {
            return;
        }

        $duelID = self::$genericDuelIDs++;

        /** @var PracticePlayer[] $players */
        $players = $args[0];
        /** @var IKit $kit */
        $kit = $args[1];
        /** @var BasicDuelGameInfo $gameType */
        $gameType = $args[2];
        /** @var bool $found */
        $found = $args[3];

        // Generates a random arena.
        $randomArena = $this->randomArena($duelID, $numPlayers = count($players));

        if ($numPlayers !== 2) {
            $duel = new BasicTeamDuel($duelID, $kit, $randomArena, $gameType, $players);
        } else {
            $duel = new Basic1vs1($duelID, $kit, $randomArena, $players[0], $players[1], $gameType);
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
        if ($game instanceof IBasicDuel && isset($this->duels[$game->getID()])) {
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
        return self::NAME;
    }

    /**
     * @return string
     *
     * Gets the title of the type of game.
     */
    public function getDisplayName(): string
    {
        return "Basic Duels";
    }

    /**
     * @return ButtonTexture|null
     *
     * Gets the form button texture.
     */
    public function getFormButtonTexture(): ?ButtonTexture
    {
        return new ButtonTexture(ButtonTexture::TYPE_PATH, "textures/ui/fire_resistance_effect.png");
    }

    /**
     * Called when the game manager is first registered.
     */
    public function onRegistered(): void
    {
        // Loads the arena manager.
        $this->arenaManager->load();

        // Must be registered before the forms.
        BasicDuelsUtils::registerDisplayStats();
        BasicDuelsUtils::registerPlayerSettings();
        BasicDuelsUtils::registerPlayerStatistics();

        // Registers the scoreboard manager.
        PracticeCore::getBaseScoreboardDisplayManager()->registerScoreboardManager(
            new BasicDuelsScoreboardManager($this->core), true);
        // Registers the form manager.
        PracticeCore::getBaseFormDisplayManager()->registerFormDisplayManager(
            new BasicDuelsFormManager($this->core), true);
        // Registers the message manager.
        PracticeCore::getBaseMessageManager()->register(
            new BasicDuelsMessageManager($this->core), true
        );

        // Initializes the generators.
        BasicDuelsUtils::initGenerators();
        // Clears the levels from the previous games.
        $this->removeLevels();
    }

    /**
     * Called when the game manager is unregistered.
     */
    public function onUnregistered(): void
    {
        BasicDuelsUtils::unregisterDisplayStats();
        BasicDuelsUtils::unregisterPlayerSettings();
        BasicDuelsUtils::unregisterPlayerStatistics();
    }

    /**
     * Called when the game manager has been saved.
     */
    public function onSave(): void
    {
        $this->arenaManager->save();
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
        $duelArena = $this->arenaManager->randomArena();
        if($duelArena !== null)
        {
            // Closes the arena.
            $this->arenaManager->close($duelArena);
            return $duelArena;
        }

        $levelName = "game.duels.basic.{$duelID}";

        /** @var BasicDuelsGeneratorInfo $randomGenerator */
        $randomGenerator = PracticeGeneratorManager::randomGenerator(
            function(PracticeGeneratorInfo $info) use ($numPlayers)
            {
                $type = $numPlayers === 2 ? BasicDuelsGeneratorInfo::TYPE_1VS1 : BasicDuelsGeneratorInfo::TYPE_TEAM;
                return $info instanceof BasicDuelsGeneratorInfo
                    && ($info->getType() === BasicDuelsGeneratorInfo::TYPE_ANY
                        || $info->getType() === $type);
            }
        );

        $this->server->generateLevel($levelName, null, $randomGenerator->getClass());
        $this->server->loadLevel($levelName);
        return new PostGeneratedDuelArena($levelName, $randomGenerator);
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
     * @param Player $player
     * @return ISpectatorGame|null
     *
     * Gets the game from the spectator.
     */
    public function getFromSpectator(Player $player): ?ISpectatorGame
    {
        foreach($this->duels as $duel)
        {
            if($duel->isSpectator($player))
            {
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
     * @return PracticeArenaManager
     *
     * Gets the game's arena manager.
     */
    public function getArenaManager(): PracticeArenaManager
    {
        return $this->arenaManager;
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

    /**
     * @return BasicDuelGameInfo[]
     *
     * Gets the duels game types.
     */
    public function getGameTypes()
    {
        return $this->gameTypes;
    }

    /**
     * @param Player $player - The player that selected it.
     *
     * Called when the game is selected in the Play Games Form.
     */
    public function onGameSelected(Player $player): void
    {
        $formManager = PracticeCore::getBaseFormDisplayManager()->getFormManager(BasicDuelsFormManager::NAME);
        if($formManager !== null)
        {
            $form = $formManager->getForm(BasicDuelsFormManager::TYPE_SELECTOR_FORM);
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
        return $this->leaderboards;
    }

    /**
     * Removes the duel levels.
     */
    private function removeLevels(): void
    {
        $directory = $this->server->getDataPath() . "worlds/";
        if(!is_dir($directory))
        {
            return;
        }

        $files = scandir($directory);
        foreach($files as $file)
        {
            if(strpos($file, "game.duels.basic.") !== false)
            {
                PracticeUtil::deleteLevel($file, false);
            }
        }
    }

    /**
     * @return ISpectatorGame[]
     *
     * Gets all of the games.
     */
    public function getGames()
    {
        $games = [];

        foreach($this->duels as $duel)
        {
            if($duel instanceof AbstractDuel && $duel instanceof IBasicDuel)
            {
                $status = $duel->getStatus();

                if($status < AbstractDuel::STATUS_ENDING && $duel->isVisibleToSpectators())
                {
                    $games[] = $duel;
                }
            }
        }

        return $games;
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
}