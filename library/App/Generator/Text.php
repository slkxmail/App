<?php

namespace App\Generator;

class Text {
    /**
     * @var array Массив синонимов
     */
    protected $synonymArray = array();

    /**
     * Как посчитать количесвто вариантов текста
     * Считаем количество вариантов которыми мы можем написать строку
     *      количество вариантов = произведению кол-ва всех синонимов.
     *
     * Счиатем количество уникальных слов в строке для которых нашли синоним
     *      считаем количество синонимов для каждого слова
     *
     */


    public function generate($template, $showSynonym = false)
    {
        $resultString = '';

        // Разбиваем текст на строки
        if (preg_match_all('#([^\.\;\!\?]+)([\.\;\!\?])#i', $template, $m)) {

            // Перебираем строки
            foreach ($m[1] as $k => $v) {
                $isFirstWord = true;

                $v = trim($v);
                // Разбираем строку на слова
                $wordsInLine = explode(' ', $v);
                foreach ($wordsInLine as $word) {
                    $wordSeparator = '';
                    if(preg_match('#[^\,\:]+([\,\:])#i', $word, $m1)){
                        $wordSeparator = $m1[1];
                    }
                    //die;
                    $word = $this->prepareWord($word);
                    $_synonym = '';
                    if (strlen($word) > 3 && $word[0] != '%') {
                        $_synonym = $this->getSynonym($word);
                    }

                    if ($_synonym) {
                        $_tWord = $showSynonym?'<b>' . $word . '</b>':$this->getSynonym($word);
                    } else {
                        $_tWord = $word;
                    }

                    $resultString .= ($isFirstWord?\App\Str\Str::ucfirst($_tWord):' '.$_tWord) . $wordSeparator;
                    $isFirstWord = false;
                }
                // Берем каждое слово ищем для него синоним

                // Если нашли синоним заменяем слово на синоним
                // Если синоним не нашли пишем старое слово
                $resultString .= $m[2][$k] . ' ';
            }
        } else {
            return 'no line';
        }
        return $resultString;
    }

    public function getSynonym($word)
    {
        $synonymModel = \Model\SynonymModel::getInstance();
        $tempWord = \App\Str\Str::lower($word);
        $tempWord = trim($tempWord);

        if (!array_key_exists($tempWord, $this->synonymArray) || count($this->synonymArray[$tempWord]) < 1) {
            // Поиск синонимов
            $synonym = $synonymModel->getByTitleHash(sha1($tempWord));
            if ($synonym->exists()) {
                $this->synonymArray[$tempWord] = explode('|', $synonym->getOption());
            }
        }
        // Если НЕТ синонима
        if (!array_key_exists($tempWord, $this->synonymArray) || count($this->synonymArray[$tempWord]) < 1) {
            return ;//$tempWord;
        }

        $randKey = array_rand($this->synonymArray[$tempWord], 1);
        if (array_key_exists($randKey, $this->synonymArray[$tempWord])) {
            $result = $this->synonymArray[$tempWord][$randKey];
            unset($this->synonymArray[$tempWord][$randKey]);
            return $result;
        }
        //print_r($synonym->toArray(true));
    }

    public function getSynonymArray($word)
    {
        $synonymModel = \Model\SynonymModel::getInstance();
        $tempWord = \App\Str\Str::lower($word);
        $tempWord = trim($tempWord);
        $synonymArray = array();

        $synonym = $synonymModel->getByTitleHash(sha1($tempWord));
        if ($synonym->exists()) {
            $synonymArray = explode('|', $synonym->getOption());
        }
        return $synonymArray;
    }

    /**
     * Считате количество уникльных вариантов в строке
     *
     * @param $templateRow
     * @return float
     */
    public function countUniqueLine($templateRow)
    {
        // Перебираем строки
        $templateRow = trim($templateRow);
        // Разбираем строку на слова
        $wordsInLine = explode(' ', $templateRow);
        # Считаем количество слова для которых есть синонимы.

        $countAllSynonym = 0;
        $countWords = 0;
        foreach ($wordsInLine as $word) {
            $wordSeparator = '';
            if(preg_match('#[^\,\:]+([\,\:])#i', $word, $m1)){
                $wordSeparator = $m1[1];
            }

            $word = trim($word);
            $word = $this->prepareWord($word);
            $_synonymArray = array();
            if (strlen($word) > 3) {
                $_synonym = $this->getSynonymArray($word);
                if ($_synonym) {
                    $_synonymArray[$word]['synonym'] = $_synonym;
                    # Считаем количество синонимов для каждого слова.
                    $_count = count($_synonymArray[$word]['synonym']);
                    $countAllSynonym += $_count;
                    $_synonymArray[$word]['count'] = count($_count);
                    $countWords++;
                }
            }

        }

        $countAverage = $countAllSynonym*($countWords-1);

        return $countAverage;
    }


    public function showSynonymInTemplates($template)
    {
        // Перебираем строки
        $template = trim($template);
        // Разбираем строку на слова
        $wordsInLine = explode(' ', $template);
        # Считаем количество слова для которых есть синонимы.
        $resultString = '';

        foreach ($wordsInLine as $word) {
            $word = trim($word);
            $word = $this->prepareWord($word);
            $wordSeparator = '';
            if(preg_match('#[^\,\:]+([\,\:])#i', $word, $m1)){
                $wordSeparator = $m1[1];
            }
            if (strlen($word) > 3) {
                $_synonym = $this->getSynonymArray($word);
                if ($_synonym) {
                    $_word = '<b>' . $word . '</b>' . $wordSeparator;
                    #$resultString .= ($isFirstWord?\App\Str\Str::ucfirst($_tWord):' '.$_tWord) . $wordSeparator;
                    #$isFirstWord = false;
                } else {
                    $_word = $word . $wordSeparator;
                }
                $resultString .= $_word;
            }

        }

        //$countAverage = $countAllSynonym*($countWords-1);

        return $resultString;
    }

    public function prepareWord($string)
    {
        $string = trim($string);
        $string = preg_replace('#[\,\.\;\:\'\"\,\.\/\?\!\@\#\$\^\&\*\`\~]#i','', $string);
        return $string;
    }
}