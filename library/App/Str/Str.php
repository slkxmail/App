<?php
namespace App\Str;
/**
 * Класс для работы со строками
 */
class Str
{
	static private $_encoding = 'UTF-8';

	static public $_entity = array(
			'&nbsp;'   => ' ',
			'&laquo;'  => '"',
			'&raquo;'  => '"',
			'&quot;'  => '"',
			'&#150;'   => '-',
			'&#151;'   => '-',
			'&#39;'    => "'",
			'&amp;'    => "&",
			'&apos;'    => "'",
			'&lt;'    => "<",
			'&gt;'    => ">",
			'«'    => '"',
			'»'    => '"',
			'„'    => '"',
			'“'    => '"',
			'—'    => '-',
			'’'    => "'",
			'…'    => '...'
		);

	static public function replaceEntity($str)
	{
		$keys = array_keys(self::$_entity);
		$values  = array_values(self::$_entity);
		return str_replace($keys, $values, $str);
	}

	static public function entityDecode($value)
	{
		$value = htmlspecialchars_decode($value);
		$value = html_entity_decode($value, ENT_QUOTES, 'UTF-8');

		$value = self::replaceEntity($value);
		return $value;
	}

	static public function setEncoding($encoding = 'UTF-8')
	{
		self::$_encoding = $encoding;
	}

	static public function uniqid()
	{
		return sha1(uniqid(rand(), true));
	}

	static public function stripEndPuntuation($str)
	{
		return preg_replace('#[\.\,\?\!\:\;]+$#si','',trim($str));
	}

	static public function strpos($haystack, $needle, $offset = null)
	{
		return mb_strpos($haystack, $needle, $offset, self::$_encoding);
	}

	static public function len($str)
	{
		return self::strlen($str);
	}

	static public function length($str)
	{
		return self::strlen($str);
	}

	static public function strlen($str)
	{
		return mb_strlen($str, self::$_encoding);
	}

	/**
	 * Переводит строку в нижний регистр
	 *
	 * @param string $str
	 * @return string Строка в нижнем регистре
	 */
	static public function lower($str)
	{
		return mb_strtolower($str, self::$_encoding);
	}

	static public function upper($str)
	{
		return mb_strtoupper($str, self::$_encoding);
	}

	static public function ucfirst($str)
	{
		return mb_strtoupper(self::substr($str, 0, 1), self::$_encoding) . self::substr($str, 1);
	}

	static public function ucwords($str)
	{
		return mb_convert_case($str, MB_CASE_TITLE, 'UTF-8');
	}

	static public function substr($str, $start, $end=false)
	{
		if ( false === $end ) {
			$end = mb_strlen($str);
		}
		return mb_substr($str, $start, $end, self::$_encoding);
	}

	static public function capitalize($str)
	{
		return mb_convert_case($str, MB_CASE_TITLE, self::$_encoding);
	}

	static public function rnd($pass_len = 10, $chars='' )
	{
		$chrs = 'abcdefghijklnmopqrstuvwxyzABCDEFGHIJKLNMOPQRSTUVWXYZ';
		$chars==''?$chars=$chrs:null;

		$string = '';
		mt_srand ((double) microtime() * 1000000);

		for ($i = 0; $i < $pass_len; $i++) {
		$string .= $chars{mt_rand (0,strlen($chars)-1)};
		}

		return $string;
	}

	public static function truncate($string, $length = 80, $etc = '', $break_words = false, $middle = false)
	{
		$string = trim(strip_tags($string));

		if ($length == 0)
			return '';

		if (is_callable('mb_strlen')) {
			if (mb_strlen($string) > $length) {
				$length -= min($length, mb_strlen($etc, self::$_encoding));
				if (!$break_words && !$middle) {
					$string = mb_ereg_replace('/\s+?(\S+)?$/', '', mb_substr($string, 0, $length + 1, self::$_encoding), 'p');
				}
				if (!$middle) {
					return mb_substr($string, 0, $length, self::$_encoding) . $etc;
				} else {
					return mb_substr($string, 0, $length / 2, self::$_encoding) . $etc . mb_substr($string, - $length / 2, null, self::$_encoding);
				}
			} else {
				return $string;
			}
		} else {
			if (strlen($string) > $length) {
				$length -= min($length, strlen($etc));
				if (!$break_words && !$middle) {
					$string = preg_replace('/\s+?(\S+)?$/', '', substr($string, 0, $length + 1, self::$_encoding));
				}
				if (!$middle) {
					return substr($string, 0, $length) . $etc;
				} else {
					return substr($string, 0, $length / 2) . $etc . substr($string, - $length / 2);
				}
			} else {
				return $string;
			}
		}
	}


	/**
	 * Получить из адреса название домена
	 *
	 * @param string $str
	 * @param boolean $withWww
	 * @return string
	 */
	public static function getDomain($str, $withWww = false)
	{
		$str = str_replace('http://', '', $str);

		if (!$withWww) {
			$str = preg_replace('#^www\.#us', '', self::lower($str));
		}

		return self::substr($str, 0, self::strpos($str, '/'));
	}

	/**
	 * Добавить http://
	 *
	 * @param string $str
	 * @return string
	 */
	public static function addHttp($str)
	{
		if (self::substr(self::lower($str), 0, 7) != 'http://') {
			return 'http://' . $str;
		} else {
			return $str;
		}
	}

	public function makeHash($str)
	{
		$str = trim(self::lower($str));
		$str = preg_replace("#[^a-z0-9а-я-]+#usi", ' ', $str);
		$strArray = explode(' ', $str);
		asort($strArray);
		$strArray = array_filter($strArray, create_function('$var', 'return mb_strlen($var, "UTF-8") > 2;') );
		$strArray = array_unique($strArray);

		return sha1(implode(' ', $strArray));
	}


	public function getMaxString($a, $b)
	{
		$a = trim(strip_tags($a));
		$b = trim(strip_tags($b));

		if (self::len($a) < self::len($b)) {
			return $b;
		} else {
			return $a;
		}
	}
    
    /**
     * Сделать из относительного URL полный
     * 
     * При проблемах возвращает $relativeUrl
     * 
     * @param string $relativeUrl Онтосительный URL
     * @param string $baseUrl
     * @return string
     */
    public static function makeAbsoluteUrl($relativeUrl, $baseUrl = false, $stripFragment = false)
    {
        $relativeUrlParts = parse_url($relativeUrl);

        $result = null;
        if ($baseUrl) {
            $baseUrlParts = parse_url($baseUrl);
            
            if (isset($baseUrlParts['scheme'])) {
                if (!isset($relativeUrlParts['scheme'])) {
                    $opts = $stripFragment ? (HTTP_URL_JOIN_PATH | HTTP_URL_STRIP_FRAGMENT) : HTTP_URL_JOIN_PATH;
                    $result = http_build_url($baseUrl, $relativeUrlParts, $opts);
                }
            }
        }
        
        if (!$result) {
            if (!isset($relativeUrlParts['scheme'])) {
                $result = $relativeUrl;
            } else {
                if ($stripFragment) {
                    $result = http_build_url($relativeUrl, array(), HTTP_URL_STRIP_FRAGMENT);
                } else {
                    $result = http_build_url($relativeUrl);
                }
            }
        }
        
        return $result;
    }

    /**
     * @param $count Количество
     * @param array $selectedArray Массив для выбора
     */
    public static function select135($count, $selectedArray = array())
    {
        $result = '';
        if ($count == 1) {
            $result = $selectedArray[0];
        } else if ($count > 1 && $count <= 4 ) {
            $result = $selectedArray[1];
        } else if ($count > 4) {
            $result = $selectedArray[2];
        }

        return $count. ' ' .$result;
    }

}
