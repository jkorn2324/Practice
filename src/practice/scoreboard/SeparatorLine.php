<?php
/**
 * Created by PhpStorm.
 * User: jkorn2324
 * Date: 2019-05-10
 * Time: 16:47
 */

declare(strict_types=1);

namespace practice\scoreboard;

use pocketmine\utils\TextFormat;

class SeparatorLine extends ScoreboardLine
{

    private $text;

    private $id;

    private $hidden;

    private $visible;

    private $format;

    public function __construct(int $id, string $format, bool $visibleSeparator = true) {
        $this->text = "*";
        $this->format = $format . TextFormat::WHITE . " ";
        $this->visible = $visibleSeparator;
        $this->id = $id;
        $this->hidden = false;
    }

    public function getId() : int { return $this->id; }

    public function setHidden(bool $hidden = true) : self {
        $this->hidden = $hidden;
        return $this;
    }

    public function isHidden(): bool
    {
        return $this->hidden;
    }

    public function isVisible() : bool {
        return $this->visible;
    }

    public function editText(string $text) : self {
        $this->text = $text;
        return $this;
    }

    public function getText() : string
    {
        return $this->format . $this->text;
    }
}