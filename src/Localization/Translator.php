<?php

namespace B4nan\Localization;

use Nette\Neon\Neon;
use Nette\Neon\Exception as NeonException;
use Nette\Localization\ITranslator;
use Nette\Caching\Cache;
use Nette\Caching\IStorage;
use Nette\Utils\Strings;
use Nette\Utils\Finder;

/**
 * simple translator, works with neon files stored in files named <code>.neon
 *
 * @author Martin Adámek <martinadamek59@gmail.com>
 */
final class Translator extends \Nette\Object implements ITranslator
{

	/** @var string */
	const VERSION = '2.2';

	/** @var string current language / code */
	private $lang;

	/** @var array translation array */
	private $dictionary;

	/** @var Cache */
	private $cache;

	/** @var array */
	private static $trailingChars = ['.', ',', ':', '!', '?', '…'];

	/**
	 * init the translator, build translation array
	 *
	 * @param string $path where to look for l10n files
	 * @param string $lang language code
	 * @param IStorage $storage
	 */
	public function __construct($path, $lang, $storage)
	{
		$this->lang = $lang;

		// try cached dictionary
		$this->cache = new Cache($storage, 'B4nan.Translator');
		$dictionary = $this->cache->load('dictionary');
		if ($dictionary === NULL) {
			$dictionary = $this->createDictionary($path);
			$dictionaries = glob("$path/*.neon"); // l10n files
			$this->cache->save('dictionary', $dictionary, [
				Cache::FILES => $dictionaries,
			]);
		}

		$this->dictionary = $dictionary;
	}

	/**
	 * build translation array
	 *
	 * @param $path
	 * @return array
	 * @throws TranslatorException
	 */
	private function createDictionary($path)
	{
		$dictionary = [];
		$files = Finder::findFiles('*.neon')->in($path); // l10n files

		foreach ($files as $file) {
			$lang = substr($file, strlen($path) + 1, -5); // extract lang code
			try {
				$dictionary[$lang] = (array) Neon::decode(file_get_contents($file));
			} catch (NeonException $e) {
				$line = Strings::match($e->getMessage(), '~([0-9]+)~');
				throw new TranslatorException("Translation file '$lang.neon' is not in correct format: " . $e->getMessage(), 0, 1, "$path/$lang.neon", $line[1], $e);
			}
		}

		return $dictionary;
	}

	/**
	 * gets supported languages in associative array: code => name
	 *
	 * @return array
	 */
	public function getSupportedLanguages()
	{
		$languages = [];

		foreach ($this->dictionary as $code => $lang) {
			$languages[$code] = $lang['name'];
		}

		return $languages;
	}

	/**
	 * translate given string
	 *
	 * @param string|array $message
	 * @param string|int $count
	 * @return string
	 */
	public function translate($message, $count = NULL)
	{
		$params = [];

		// message is array, split into string and params
		if (is_array($message)) {
			$params = array_slice($message, 1);
			$message = $message[0];
			if (isset($params[0]) && is_numeric($params[0]) && $count === NULL) {
				$count = (int) $params[0];
			}
		}

		// try to translate
		$message = (string) $message;
		if (isset($this->dictionary[$this->lang]['translations'][$message])) {
			$message = $this->dictionary[$this->lang]['translations'][$message];
		} else {
			// try to find translation for message with specific trailing character
			foreach (self::$trailingChars as $char) {
				if (mb_substr($message, -1) === $char && isset($this->dictionary[$this->lang]['translations'][mb_substr($message, 0, -1)])) {
					$message = $this->dictionary[$this->lang]['translations'][mb_substr($message, 0, -1)] . $char;
				}
			}
		}
		if (is_array($message)) { // plural forms
			$form = $this->getForm($count);
			$message = $message[$form];
		}

		// perform vsprintf
		if (!empty($params)) {
			$message = vsprintf($message, $params);
		}

		// perform vsprintf if there are any params
		if ($count && func_num_args() > 1) {
			$message = vsprintf($message, array_slice(func_get_args(), 1));
		}

		// strip all not replaces %s
		$message = str_replace('%s', '', $message);

		return $message;
	}

	/**
	 * current language setter
	 *
	 * @param string $lang
	 */
	public function setLang($lang)
	{
		if (!empty($lang)) {
			$this->lang = $lang;
		}
	}

	/**
	 * @return string
	 */
	public function getLang()
	{
		return $this->lang;
	}

	/**
	 * gets plural form
	 *
	 * @param int $count
	 * @return int plural form index
	 */
	private function getForm($count)
	{
		$count = abs(strip_tags($count));
		if ($count <= 1) {
			return 0; // singular
		}

		$forms = explode('|', $this->dictionary[$this->lang]['plural']['form']);
		$form = 0;
		for ($i = 0; $i < count($forms); $i++) {
			if ($count >= $forms[$i]) {
				$form = $i + 1;
			}
		}

		return $form;
	}

}
