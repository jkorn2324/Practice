<?php

declare(strict_types=1);

namespace jkorn\bd;


use jkorn\bd\arenas\ArenaManager;
use jkorn\bd\arenas\IDuelArena;
use jkorn\bd\arenas\PostGeneratedDuelArena;
use jkorn\bd\duels\Basic1vs1;
use jkorn\bd\duels\BasicTeamDuel;
use jkorn\bd\duels\types\BasicDuelGameType;
use jkorn\bd\forms\BasicDuelsFormManager;
use jkorn\bd\gen\BasicDuelsGeneratorInfo;
use jkorn\bd\duels\IBasicDuel;
use jkorn\bd\queues\BasicQueuesManager;
use jkorn\practice\forms\display\FormDisplay;
use jkorn\practice\games\IGame;
use jkorn\practice\games\misc\IAwaitingGameManager;
use jkorn\practice\games\misc\IAwaitingManager;
use jkorn\practice\games\misc\IGameType;
use jkorn\practice\kits\IKit;
use jkorn\practice\level\gen\PracticeGeneratorInfo;
use jkorn\practice\level\gen\PracticeGeneratorManager;
use jkorn\practice\player\PracticePlayer;
use pocketmine\Player;
use pocketmine\Server;
use jkorn\practice\PracticeCore;

class BasicDuelsManager implements IAwaitingGameManager
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

    public function __construct()
    {
        $this->server = PracticeCore::getInstance()->getServer();
        $this->duels = [];

        $this->initGameTypes();
        $this->queuesManager = new BasicQueuesManager($this);
    }

    /**
     * Initializes the game types.
     */
    private function initGameTypes(): void
    {
        $this->registerGameType(new BasicDuelGameType(2,
            "1vs1", "textures/ui/dressing_room_customization.png"));
        $this->registerGameType(new BasicDuelGameType(4,
            "2vs2", "textures/ui/FriendsDiversity.png"));
        $this->registerGameType(new BasicDuelGameType(6,
            "3vs3", "textures/ui/dressing_room_skins.png"));
    }

    /**
     * @param BasicDuelGameType $gameType
     *
     * Registers the game type.
     */
    private function registerGameType(BasicDuelGameType $gameType): void
    {
        $this->gameTypes[$gameType->getLocalizedName()] = $gameType;
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
        if (count($args) !== 4) {
            return;
        }

        $duelID = self::$genericDuelIDs++;

        /** @var PracticePlayer[] $players */
        $players = $args[0];
        /** @var IKit $kit */
        $kit = $args[1];
        /** @var BasicDuelGameType $gameType */
        $gameType = $args[2];
        /** @var bool $found */
        $found = $args[3];

        // Generates a random arena.
        $randomArena = $this->randomArena($duelID, count($players));

        if (count($players) !== 2) {
            $duel = new BasicTeamDuel($duelID, $kit, $randomArena, $gameType, ...$players);
        } else {
            $duel = new Basic1vs1($duelID, $kit, $randomArena, $players[0], $players[1]);
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
    public function getTitle(): string
    {
        return "Basic Duels";
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
        // Registers the arena manager generator.
        PracticeCore::getBaseArenaManager()->registerArenaManager(new ArenaManager(), true);

        // Initializes the generators.
        BasicDuelsUtils::initGenerators();

        BasicDuelsUtils::registerFormDisplayStats();
        BasicDuelsUtils::registerPlayerSettings();
        BasicDuelsUtils::registerPlayerStatistics();
        BasicDuelsUtils::registerScoreboardStatistics();
    }

    /**
     * Called when the game manager is unregistered.
     */
    public function onUnregistered(): void
    {
        PracticeCore::getBaseArenaManager()->unregisterArenaManager(ArenaManager::TYPE);

        BasicDuelsUtils::unregisterFormDisplayStats();
        BasicDuelsUtils::unregisterPlayerSettings();
        BasicDuelsUtils::unregisterPlayerStatistics();
        BasicDuelsUtils::unregisterScoreboardStatistics();
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
        $duelArenaManager = PracticeCore::getBaseArenaManager()->getArenaManager(ArenaManager::TYPE);
        if($duelArenaManager instanceof ArenaManager)
        {
            $duelArena = $duelArenaManager->randomArena();
        }

        if(!isset($duelArena) || $duelArena === null)
        {
            $levelName = "game.duels.basic.{$duelID}";
            /** @var BasicDuelsGeneratorInfo $randomGenerator */
            $randomGenerator = PracticeGeneratorManager::randomGenerator(
                function(PracticeGeneratorInfo $info) use($numPlayers)
                {
                    $type = $numPlayers === 2 ? BasicDuelsGeneratorInfo::TYPE_1VS1 : BasicDuelsGeneratorInfo::TYPE_TEAM;
                    return $info instanceof BasicDuelsGeneratorInfo
                        && ($info->getType() === BasicDuelsGeneratorInfo::TYPE_ANY
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

    /**
     * @return BasicDuelGameType[]
     *
     * Gets the duels game types.
     */
    public function getGameTypes()
    {
        return $this->gameTypes;
    }

    /**
     * @return FormDisplay|null
     *
     * Gets the corresponding form used to put the player in the game.
     */
    public function getGameSelector(): ?FormDisplay
    {
        $manager = PracticeCore::getBaseFormDisplayManager()->getFormManager(BasicDuelsFormManager::NAME);
        if($manager !== null)
        {
            return $manager->getForm(BasicDuelsFormManager::SELECTOR_FORM);
        }

        return null;
    }
}