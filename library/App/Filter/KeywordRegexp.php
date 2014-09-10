<?php

namespace App\Filter;

/**
 * Получить стем от фразы
 * 
 * @see http://ru.wikipedia.org/wiki/%D0%A1%D1%82%D0%B5%D0%BC%D0%BC%D0%B5%D1%80_%D0%9F%D0%BE%D1%80%D1%82%D0%B5%D1%80%D0%B0
 */
class KeywordRegexp extends StemNoStopwords
{
	public function filter($value)
	{
        $value = parent::filter($value);

        $arrValue = explode(' ', $value);
        foreach ($arrValue as &$v) {
            if (strlen($v) > 2) {
                $v .= '[a-zA-Zа-яА-Я]{0,2}(?![a-zA-Zа-яА-Я0-9\-]+)';
            }
        }

		return implode(' ', $arrValue);
	}
}
