<?php

namespace App\Stopwords;

/**
 * Фильтрация стоп слов
 *
 * @author Eugene Myazin (meniam@gmail.com)
 */
class Stopwords
{
    private $_stopwords = array();
    private $_loadedSwopwords = false;

    public function stopwords()
    {
        return $this;
    }

    public function loadAllStopwordsFiles()
    {
        if (!$this->_loadedSwopwords || $this->_loadedSwopwords != 'stopwords')
        {
            $this->_stopwords = array();
            $this->loadFromFile(dirname(__FILE__) . '/stopwords.txt');
            $this->_loadedSwopwords = 'tags';
        }
    }


    public function loadStopwordsFiles()
    {
        if (!$this->_loadedSwopwords || $this->_loadedSwopwords != 'stopwords')
        {
            $this->_stopwords = array();
            $this->loadFromFile(dirname(__FILE__) . '/stopwords_ru.txt');
            $this->_loadedSwopwords = 'tags';
        }
    }

    public function loadTagsStopwordsFiles()
    {
        if (!$this->_loadedSwopwords || $this->_loadedSwopwords != 'tags')
        {
            $this->_stopwords = array();
            $this->loadFromFile(dirname(__FILE__) . '/tags_ru.txt');
            $this->_loadedSwopwords = 'tags';
        }
    }

    public function processStopword($str)
    {
        $this->loadAllStopwordsFiles();
        return $this->process($str);
    }

    public function processTag($str)
    {
        $this->loadTagsStopwordsFiles();
        return $this->process($str);
    }

    public function loadFromFile($file)
    {
        $contents = file_get_contents($file);
        $lines = explode("\n", $contents);

        foreach ($lines as $line) {
            $line = trim($line);
            if ($line) {
                $this->_addStopWord($line);
            }
        }
    }

    public function process($str)
    {
        if (is_string($str)) {
            $str = trim($str);
            if (empty($str)) {
                return null;
            }

            $words = explode(' ', $str);
            $resultArray = $this->_processArray($words);
        } else if(is_array($str)) {
            $resultArray = $this->_processArray($str);
        } else {
            return null;
        }

        return implode(' ', $resultArray);
    }

    public function isStopWord($word)
    {
        if (in_array(mb_strtolower(trim($word), 'UTF-8'), $this->_stopwords)) {
            return true;
        }

        return false;
    }

    public function isStopwordRegexp($word)
    {
        $word = mb_strtolower(trim($word), 'UTF-8');

        if ($this->isStopWord($word)) {
            return true;
        }

        foreach ($this->_stopwords as $stopwords)
        {
            if (preg_match("#^{$stopwords}\$#u", $word)) {
                return true;
            }
        }

        return false;
    }

    protected function _processArray($words)
    {
        if (!is_array($words)) {
            return null;
        }

        foreach ($words as $id => $word) {
            if ($this->isStopWord($word)) {
                unset($words[$id]);
            }
        }

        return $words;
    }

    protected function _addStopWord($word)
    {
        if (is_array($this->_stopwords) && in_array($word, $this->_stopwords)) {
            return false;
        } else {
            $this->_stopwords[] = mb_strtolower(trim($word), 'UTF-8');
            return true;
        }
    }
}