<?php

namespace B4nan\Tests\Localization;

use B4nan\Localization\Translator;
use B4nan\Localization\TranslatorException;
use Tester\TestCase;
use Tester\Assert;
use Nette\Caching\IStorage;
use Nette\Localization\ITranslator;
use Nette\Caching\Storages\DevNullStorage;
use Nette\Utils\FileSystem;

require __DIR__ . '/../bootstrap.php';

/**
 * Translator test
 *
 * @author Martin Adámek <martinadamek59@gmail.com>
 */
class TranslatorTest extends TestCase
{

	/** @var ITranslator */
	private $translator;

	/** @var IStorage */
	private $storage;

	public function setUp()
	{
		$path = __DIR__ . '/../langs';
		$lang = 'cs';
		$this->storage = new DevNullStorage;
		$this->translator = new Translator($path, $lang, $this->storage);
	}

	public function testSupportedLanguagesGetter()
	{
		$supported = $this->translator->getSupportedLanguages();
		Assert::equal([
			'cs' => 'Čeština',
			'en' => 'English',
		], $supported);
	}

	public function testErrorInDictionary()
	{
		$data = "name: asd\n name: dsa"; // wrong neon format - duplicity
		$temp = __DIR__ . '/temp';
		FileSystem::createDir($temp);
		file_put_contents($temp . '/cs.neon', $data);
		Assert::exception(function() use ($temp) {
			new Translator($temp, 'cs', $this->storage);
		}, TranslatorException::class, "Translation file 'cs.neon' is not in correct format: Bad indentation on line 2, column 2.");
	}

	public function testLangSetter()
	{
		$lang = 'en';
		$this->translator->setLang($lang);
		Assert::equal($lang, $this->translator->lang);
	}

	public function testSimpleTranslate()
	{
		// not existing sentence
		$sentence = 'not existing sentence.';
		$translated = $this->translator->translate($sentence);
		Assert::equal($translated, $sentence);

		// existing sentence
		$sentence = 'Next';
		$translated = $this->translator->translate($sentence);
		Assert::equal('Další', $translated);

		// existing sentence with trailing character
		$sentence = 'Next:';
		$translated = $this->translator->translate($sentence);
		Assert::equal('Další:', $translated);
	}

	public function testTranslateWithParams()
	{
		// sentence in array
		$sentence = ['Module %s successfully activated.', 'test'];
		$translated = $this->translator->translate($sentence);
		Assert::equal('Rozšíření test úspěšně aktivováno.', $translated);

		// parameter in $count
		$sentence = 'Module %s successfully activated.';
		$translated = $this->translator->translate($sentence, 'test');
		Assert::equal('Rozšíření test úspěšně aktivováno.', $translated);
	}

	public function testTranslatePlural()
	{
		// sentence in array
		$translated = $this->translator->translate(['%s days', 1]);
		Assert::equal('1 den', $translated);
		$translated = $this->translator->translate(['%s days', 2]);
		Assert::equal('2 dny', $translated);
		$translated = $this->translator->translate(['%s days', 3]);
		Assert::equal('3 dny', $translated);
		$translated = $this->translator->translate(['%s days', 5]);
		Assert::equal('5 dní', $translated);
		$translated = $this->translator->translate(['%s days', 101]);
		Assert::equal('101 dní', $translated);

		// parameter in $count
		$translated = $this->translator->translate('%s days', 1);
		Assert::equal('1 den', $translated);
		$translated = $this->translator->translate('%s days', 2);
		Assert::equal('2 dny', $translated);
		$translated = $this->translator->translate('%s days', 3);
		Assert::equal('3 dny', $translated);
		$translated = $this->translator->translate('%s days', 5);
		Assert::equal('5 dní', $translated);
		$translated = $this->translator->translate('%s days', 101);
		Assert::equal('101 dní', $translated);
	}

	public function tearDown()
	{
		$temp = __DIR__ . '/temp';
		FileSystem::delete($temp);
	}

}

// run test
(new TranslatorTest)->run();
