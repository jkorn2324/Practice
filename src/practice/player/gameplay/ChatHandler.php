<?php
/**
 * Created by PhpStorm.
 * User: jkorn2324
 * Date: 2019-04-18
 * Time: 18:55
 */

declare(strict_types=1);

namespace practice\player\gameplay;


use practice\PracticeUtil;

class ChatHandler
{
    private $contents = [];

    public function __construct() {
        $path = substr(__DIR__, 0, strpos(__DIR__, "/src/"));
        $contents = file($path . "/resources/filtered_words.txt");

        foreach($contents as $content) {
            $content = strtolower(trim($content));
            $this->contents[$content] = true;
        }
    }


    /**
     * @param string $msg
     * @return array|string[]
     */
    public function getCensoredWordsIn(string $msg) : array {

        $result = [];

        $lowerCaseMsg = strtolower($msg);

        $words = explode(" ", $lowerCaseMsg);

        foreach($words as $word) {

            $word = strval($word);

            $lowerCaseWord = strtolower($word);

            if(isset($this->contents[$lowerCaseWord])) {

                $len = strlen($lowerCaseWord);

                $indexes = PracticeUtil::str_indexes($lowerCaseWord, $lowerCaseMsg);

                foreach($indexes as $index) {

                    $str = substr($msg, $index, $len);

                    if(!PracticeUtil::arr_contains_value($str, $result))
                        $result[] = $str;
                }
            }
        }

        return $result;
    }

    public function hasCensoredWords(string $msg) : bool {
        $censoredWords = $this->getCensoredWordsIn($msg);
        $count = count($censoredWords);
        return $count > 0;
    }

    public function getUncensoredMessage(string $msg) : string {

        $result = $msg;

        if($this->hasCensoredWords($msg)){

            $words = $this->getCensoredWordsIn($msg);

            $replacedWords = [];

            foreach($words as $word){

                $key = strval($word);

                $val = mb_substr($key, 0, 1) . "\u{FEFF}" . mb_substr($key, 1);

                $replacedWords[$key] = $val;
            }

            $result = PracticeUtil::str_replace($result, $replacedWords);
        }
        return $result;
    }

}