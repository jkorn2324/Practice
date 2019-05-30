<?php
/**
 * Created by PhpStorm.
 * User: jkorn2324
 * Date: 2019-05-29
 * Time: 19:47
 */

declare(strict_types=1);

namespace practice\game\items;

use practice\PracticeUtil;

class ItemTextures
{

    private $textures;

    public function __construct()
    {
        $path = substr(__DIR__, 0, strpos(__DIR__, "/src/"));
        $contents = file($path . "/resources/items.txt");

        $this->textures = [];

        foreach($contents as $content) {
            $content = trim($content);
            $index = PracticeUtil::str_indexOf(': ', $content);
            $itemName = substr($content, 0, $index);
            $itemTexture = trim(substr($content, $index + 2));
            $png = PracticeUtil::str_indexOf('.png', $itemTexture);
            $itemTexture = trim(substr($itemTexture, 0, $png));
            $this->textures[$itemName] = $itemTexture;
        }
    }

    public function getTexture(string $item) : string {
        $result = "apple";
        if(isset($this->textures[$item]))
            $result = $this->textures[$item];
        return 'textures/items/' . $result;
    }
}