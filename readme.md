B4nan\Localization\Translator
===========================


Installation
------------

register service via config.neon, and don't forget to set translator to template object

in config.neon:

```php
parameters:
	defaultLang: cs
services:
	translator: B4nan\Localization\Translator(%appDir%/langs, %defaultLang%, @cacheStorage)
```

in BasePresenter.php:

```php
/** @var \B4nan\Localization\Translator @inject */
public $translator;

protected function startup()
{
	parent::startup();
	$this->translator->setLang($this->user->identity->lang);
}
```
