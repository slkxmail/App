<?php

namespace App\Stemer;

class Stemer
{
	protected static $_stopwords = array();

    /**
     * Грузим список стопслов
     *
     * @return array
     */
    protected static function _loadStopwords()
	{
		if (empty(self::$_stopwords)) {
			self::$_stopwords = array_unique(array_map('trim', include __DIR__ . '/stopwords.php'));
		}

		return self::$_stopwords;
	}

	public function stemer()
	{
		return $this;
	}

	public static function process($str, $strip = true)
	{
		self::_loadStopwords();
		if ($strip) {
			$str = preg_replace("#[^a-zа-я0-9]+#usi",' ', mb_strtolower($str, 'UTF-8'));
			$words = explode(' ', str_replace('  ', ' ', trim($str)));
			array_walk($words, array('self', 'arrayCallback'));
			$result = implode(' ', $words);
		} else {
			$result = preg_replace_callback("#([a-zA-ZА-Яа-я0-9ЕЁеёЫы]+)#usi", array('self', 'replaceCallback'), $str);
		}
		return $result;
	}

	public static function stemWord($word)
	{
		if (!in_array($word, self::$_stopwords)) {
			$word = iconv('utf-8','KOI8-R',$word);
			$word = self::_stemWord($word);
			$word = iconv('KOI8-R','utf-8',$word);
		}

		return $word;
	}

	static function _stemWord($word)
	{
		$oldlocale = \setlocale(LC_ALL,0);
		\setlocale(LC_ALL,'C');
		$word = \stem_russian($word);
		$word = \stem_english($word);
		\setlocale(LC_ALL,$oldlocale);

		return $word;
	}

	protected static function replaceCallback($m)
	{
		return self::stemWord($m[1]);
	}

	protected static function arrayCallback(&$item,$key)
	{
		$item = self::stemWord($item);
	}
}
