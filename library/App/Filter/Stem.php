<?php

namespace App\Filter;

/**
 * Получить стем от фразы
 * 
 * @see http://ru.wikipedia.org/wiki/%D0%A1%D1%82%D0%B5%D0%BC%D0%BC%D0%B5%D1%80_%D0%9F%D0%BE%D1%80%D1%82%D0%B5%D1%80%D0%B0
 */
class Stem extends StripText
{
	public function filter($value)
	{
        $value = preg_replace('#-#is', ' ', parent::filter($value));

        // Убираем стоп слова
        $stopwords = new Stopwords();
        $value = $stopwords->filter($value);

		return \App\Stemer\Stemer::process($value, false);
	}
}
