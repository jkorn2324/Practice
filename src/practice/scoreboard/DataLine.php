<?php
/**
 * Created by PhpStorm.
 * User: jkorn2324
 * Date: 2019-05-10
 * Time: 16:56
 */

namespace practice\scoreboard;


use practice\PracticeUtil;

class DataLine extends ScoreboardLine
{

    private $text;

    private $id;

    private $hidden;

    private $originalText;

    public function __construct(int $id, string $text) {
        $this->id = $id;
        $this->originalText = $text;
        $this->text = $text;
        $this->hidden = false;
    }

    public function updateText(array $arr) : self {
        $this->text = PracticeUtil::str_replace($this->originalText, $arr);
        return $this;
    }

    public function setHidden(bool $hidden = true) : self {
        $this->hidden = $hidden;
        return $this;
    }

    public function editText(string $text) : self {
        $this->text = $text;
        return $this;
    }

    public function getId() : int { return $this->id; }

    public function getText(): string {
        return " " . $this->text;
    }

    public function isHidden(): bool
    {
        return $this->hidden;
    }
}