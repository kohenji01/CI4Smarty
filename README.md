# Smarty for CodeIgniter4

CodeIgniter4用のSmarty組み込みです。


# Install

デフォルトでは、次のようなパスで動作します。

| 種類         | パス                        |
|:-------------|:----------------------------|
| テンプレート | app/Views                   |
| コンパイル   | writable/smarty/templates_c |
| キャッシュ   | writable/smarty/cache       |
| コンフィグ   | writable/smarty/config      |

これらのディレクトリを作って適切なパーミッションを与えることで動作します。

もしこのパスを変更したい場合は`.env`ファイルに次のパラメータをセットすることで、任意のパスに変更可能です。

```bash
CI4Smarty.TemplateDir = /path/to/TemplateDir
CI4Smarty.CompileDir = /path/to/CompileDir
CI4Smarty.CacheDir = /path/to/CacheDir
CI4Smarty.ConfigDir = /path/to/ConfigDir
```

# Usage

### view()
CI4のview関数をSmarty用にオーバーライド可能です。

`app`ディレクトリ直下の`Common.php`に次を追記してください。

```php
require_once ROOTPATH . "vendor/sarah-systems/ci4smarty/src/Common.php";
```

使用法はCI4のview関数と同じです。

```php
view('template.tpl');
```

拡張子`.tpl`は省略可能です。

```php
view('template');
```

view関数の第2パラメータはSmarty変数`$CI`にアサインされます。

```php
$data = [ 'apple' , 'banana' , 'lemon' ];
view('template',$data);
```

Smartyのtemplate上では
```smarty
{$CI.0} <-- appleが表示されます。
```

### Service

CI4のServiceが利用可能です。

```php
use CI4Smarty\Config\Services;

$time = date('Y-m-d H:i:s');
$smarty = Services::smarty();
$smarty->assign('time',$time);
$smarty->display('template.tpl');
```