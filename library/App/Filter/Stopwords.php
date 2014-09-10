<?php

namespace App\Filter;

class Stopwords extends StripText
{
    /**
     * @var \App\Stopwords\Stopwords
     */
    protected static $_stopwords;

	public function __construct()
	{
		if (self::$_stopwords == null) {
			self::$_stopwords = new \App\Stopwords\Stopwords();
			self::$_stopwords->loadAllStopwordsFiles();
		}
	}

	public function filter($value)
	{
		return $this->_getStopwords()->process(parent::filter($value));
	}

	protected function _getStopwords()
	{
		return self::$_stopwords;
	}
}
