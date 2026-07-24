# SentencePress

[![Build Status](https://app.travis-ci.com/szepeviktor/SentencePress.svg?branch=master)](https://app.travis-ci.com/szepeviktor/SentencePress)
[![Packagist](https://img.shields.io/packagist/v/szepeviktor/sentencepress.svg?color=239922&style=popout)](https://packagist.org/packages/szepeviktor/sentencepress)
[![PHPStan](https://img.shields.io/badge/PHPStan-enabled-239922)](https://phpstan.org/)

These [tools](/src) are recommended for use in agency-type projects
where you have full control over the development and installation environment.

### Installation

```bash
composer require szepeviktor/sentencepress
```

See [WordPress website lifecycle](https://github.com/szepeviktor/wordpress-website-lifecycle)
for working with WordPress.

### Examples

```php
$context = Context::detect();

if (
    $context->is(PrimaryContext::Ajax)
    && $context->has(ContextFlag::Authenticated)
) {
    // Handle an authenticated admin-ajax.php request.
}

if (
    $context->is(PrimaryContext::Cron)
    && $context->has(ContextFlag::RuntimeCli)
) {
    // wp-cron.php is running from the command line.
}

if ($context->has(ContextFlag::WpCliContextAdmin)) {
    // WP-CLI selected its admin context.
}
```

```php
// Instead of wp_enqueue_script('main-js', get_template_directory_uri() . '/assets/js/main.js', [], '8.44', true)
$mainJs = new Script(get_template_directory_uri() . '/assets/js/main.js');
$mainJs
    ->setHandle('main-js')
    ->setVer('8.44')
    ->moveToFooter()
    ->enqueue();
```

```php
// Instead of add_action('plugins_loaded', [$this, 'init'], 20, 0);
class Plugin
{
    use HookAnnotation;

    public function __construct()
    {
        $this->hookMethods();
    }

    /**
     * @hook plugins_loaded 20
     */
    public function init(): void
    {
        doSomething();
    }
}
```

```php
// Instead of require __DIR__ . '/inc/template-functions.php';
// template-functions.php will be loaded and pingbackHeader called when wp_head hook is fired
class Template
{
    use HookProxy;

    public function __construct()
    {
        $this->lazyHookFunction(
            'wp_head',
            __NAMESPACE__ . '\\TemplateFunction\\pingbackHeader',
            10,
            0,
            __DIR__ . '/inc/template-functions.php'
        );
    }
}
```
