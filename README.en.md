# Smarty for CodeIgniter 4

[日本語](README.md) | English

CI4Smarty integrates Smarty into CodeIgniter 4 applications.

## Install

Run this command from your CodeIgniter application root.

```bash
composer require "sarah-systems/ci4smarty:^0.2"
```

## Render your first template

1. Add the following to `app/Common.php`.

   ```php
   require_once ROOTPATH . 'vendor/sarah-systems/ci4smarty/src/Common.php';
   ```

2. Create `app/Views/hello.tpl`.

   ```smarty
   <p>Hello, {$CI.name|escape}!</p>
   ```

3. Return the rendered template from a controller method that has a route.

   ```php
   public function index(): string
   {
       return \CI4Smarty\view('hello', ['name' => 'Alex']);
   }
   ```

Open that route in your browser. The page displays **Hello, Alex!**

## Configure Smarty

| Setting | Default path |
| :--- | :--- |
| Templates | `app/Views` |
| Compiled templates | `writable/smarty/templates_c` |
| Smarty cache | `writable/smarty/cache` |
| Smarty configuration files | `writable/smarty/configs` |

Make sure PHP can write to the compile directory and any cache directory in use. You can override the paths and other settings in your application's `.env` file.

```ini
CI4Smarty.TemplateDir = /path/to/templates
CI4Smarty.CompileDir = /path/to/compiled-templates
CI4Smarty.CacheDir = /path/to/smarty-cache
CI4Smarty.ConfigDir = /path/to/smarty-config
CI4Smarty.LeftDelimiter = {{
CI4Smarty.RightDelimiter = }}
CI4Smarty.Debug = false
CI4Smarty.DefaultTemplateExtension = .tpl
```

The two delimiter settings above show how to switch to `{{` and `}}`. Smarty uses `{` and `}` by default.

### Application configuration

Create `app/Config/Smarty.php` to manage Smarty settings through CodeIgniter.

```php
<?php

namespace Config;

class Smarty extends \CI4Smarty\Config\Smarty
{
    public $templateDir = [APPPATH . 'Views', APPPATH . 'SharedViews'];
    public $defaultTemplateExtension = '.tpl';
}
```

CI4Smarty searches multiple template directories in the order listed. You can also set `compileDir`, `cacheDir`, `configDir`, `leftDelimiter`, `rightDelimiter`, and `debug`. Existing `CI4Smarty.*` environment variables take precedence over the application configuration, followed by package defaults.

## Render views

Call `\CI4Smarty\view()` directly or import it with `use function CI4Smarty\view;`. Use `\view()` when calling CodeIgniter's built-in PHP view helper from the same file.

The default extension is `.tpl`, so `\CI4Smarty\view('hello')` and `\CI4Smarty\view('hello.tpl')` render the same template with the default settings. The template receives the second argument as `$CI`. For example, an array with a `name` key is available as `{$CI.name}`.

### Module templates

For example, if `Vendor\Blog` is registered with the CodeIgniter autoloader, render its article template like this.

```php
return \CI4Smarty\view('module:Vendor\Blog\article', ['title' => 'News']);
```

CI4Smarty looks for `app/Views/overrides/Vendor/Blog/article.tpl` first, then `Views/article.tpl` in the registered module. You can omit the configured extension. Use Smarty's relative paths inside the template, such as `{include file="./parts.tpl"}` or `{extends file="./layout.tpl"}`. Relative paths in an application override resolve from the override's directory.

### View options

```php
echo \CI4Smarty\view('article', ['title' => 'News'], [
    'saveData'   => false,
    'cache'      => 60,
    'cache_name' => 'article-en',
    'debug'      => false,
]);
```

| Option | Behavior |
| :--- | :--- |
| `saveData` | Defaults to `$saveData` in `app/Config/View.php`, or `true` if the setting is unavailable. Array data is merged with values retained from earlier calls in the same request. Set `false` to keep this call's additions from carrying over. Previously retained values remain available to the current call. |
| `cache` | A positive integer caches the rendered HTML for that many seconds through CodeIgniter's cache service. |
| `cache_name` | Sets the name of the HTML cache entry when `cache` is enabled. It defaults to the template name with its extension. |
| `debug` | Set `false` to suppress the HTML view markers normally added when CI4 debugging and the Views collector are enabled. Set `CI4Smarty.Debug` to control Smarty's debug console. |

Configure the HTML cache backend in CodeIgniter, usually in `app/Config/Cache.php`. `CI4Smarty.CacheDir` sets Smarty's own cache directory.

Use distinct `cache_name` values when a template produces different public content by language or other context. Leave `cache` unset for user-specific pages or forms containing a CSRF token.

### Clear an individual view cache entry

After updating content, you can remove the affected HTML cache entry. If you supplied `cache_name` when rendering, pass the same value when clearing the entry.

```php
// After updating the English version of article 42.
\CI4Smarty\clear_view_cache('article', 'article-42-en');

// Remove an entry rendered without a custom cache_name.
\CI4Smarty\clear_view_cache('article');
```

On the next request that uses the cleared key, CI4Smarty renders the template and stores the HTML again. The return value reports the result from the CodeIgniter cache handler.

| Stored content | Setting or location | Affected by this API |
| :--- | :--- | :--- |
| HTML cached by the `view()` option `cache` | CodeIgniter's cache service, usually configured in `app/Config/Cache.php` | The specified HTML entry |
| Smarty's own output cache, when enabled | `CI4Smarty.CacheDir` | No |
| Smarty's compiled templates | `CI4Smarty.CompileDir`, which defaults to `writable/smarty/templates_c` | No |

When both output caches are enabled, the next render after clearing the CodeIgniter entry can still reuse HTML cached by Smarty. If this API manages your public pages' cached output, using the CodeIgniter output cache alone makes it easier to verify when edits become visible.

### Debug Toolbar timeline

To show Smarty rendering times, add this entry to the `$collectors` array in your application's `app/Config/Toolbar.php`.

```php
\CI4Smarty\Debug\Toolbar\Collectors\SmartyViews::class,
```

With CI4 debugging enabled, the timeline shows each Smarty template name and its elapsed time. Entries served from CodeIgniter's HTML cache are marked `(cache hit)`. Calls with `debug => false` are omitted from the timeline. You can use the Smarty collector alongside the standard Views collector.

### Use the Smarty service directly

```php
use CI4Smarty\Config\Services;

$smarty = Services::smarty();
$smarty->assign('time', date('Y-m-d H:i:s'));
$smarty->display('template.tpl');
```

## Upgrade from 0.1.x

```bash
composer require "sarah-systems/ci4smarty:^0.2" --with-all-dependencies
```

- Version 0.2.0 requires Smarty 5.9 or later in the Smarty 5 series. If you are upgrading from Smarty 3 or 4, review your templates and custom plugins. [Smarty upgrade guide](https://smarty-php.github.io/smarty/5.x/upgrading/)
- When CI4's `$saveData` is `true`, data can carry over between Smarty views within a request. To use only the data passed to each call, set `$saveData = false` in `app/Config/View.php`. This setting also applies to CodeIgniter's built-in PHP views.
- Set `CI4Smarty.DefaultTemplateExtension` to an empty string to disable extension completion. `CI4Smarty.Debug` accepts `0`, `1`, `false`, and `true`.

## Compatibility

Composer constraints are PHP `^7.2 || ^8.0`, CodeIgniter `^4.0`, and Smarty `^5.9`. PHP 8.5 requires CodeIgniter 4.7 or later. Check the framework's requirements for the PHP and CI4 versions you intend to use. [CodeIgniter server requirements](https://codeigniter.com/user_guide/intro/requirements.html)

The following combinations passed integration tests on Windows. GitHub Actions also tests the corresponding version series. [Compatibility workflow](https://github.com/kohenji01/CI4Smarty/actions/workflows/compatibility.yml)

| PHP | CodeIgniter | Smarty |
| :--- | :--- | :--- |
| 7.2.34 | 4.0.0 | 5.9.0 |
| 7.3.33 | 4.0.5, 4.1.9 | 5.9.0 |
| 7.4.33 | 4.2.12 | 5.9.0 |
| 8.0.30 | 4.3.8 | 5.9.0 |
| 8.1.34 | 4.5.8 | 5.9.0 |
| 8.2.29 | 4.7.6 | 5.9.0 |
| 8.3.28 | 4.4.8 | 5.9.0 |
| 8.4.15 | 4.6.5 | 5.9.0 |
| 8.5.12 | 4.7.6 | 5.9.0 |

## Changelog

See the [English changelog](CHANGELOG.en.md) for release and upgrade notes.

## License

The source code is available under the MIT License. [LICENSE](LICENSE)
