# Changelog

[日本語](CHANGELOG.md) | English

## 0.2.1

- Added an English README and changelog.

## 0.2.0

- Requires Smarty 5.9 or later in the Smarty 5 series. If you are upgrading from Smarty 3 or 4, review your templates and custom plugins. [Smarty upgrade guide](https://smarty-php.github.io/smarty/5.x/upgrading/)
- Tested combinations of PHP 7.2 through 8.5 and CodeIgniter 4.0 through 4.7. [Tested versions](README.en.md#compatibility)
- Added the `saveData`, `cache`, `cache_name`, and `debug` options to `\CI4Smarty\view()`. You can now include the configured extension in the template name without it being appended twice.

### Upgrade notes

- When `saveData` is omitted, CI4Smarty uses `$saveData` from `app/Config/View.php`. Passing `['saveData' => false]` keeps the current call's additions from carrying over, while previously retained values remain available to that call. Set `$saveData = false` in the view configuration if each call should use only its own data. This setting also applies to CodeIgniter's built-in views.
- When HTML caching is enabled, views of the same template share a cache entry by default. Set distinct `cache_name` values when public content varies by language or another context. Leave `cache` unset for user-specific pages or forms containing a CSRF token.
