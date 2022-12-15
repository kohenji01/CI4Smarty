# Smarty for CodeIgniter4

CodeIgniter4用のSmarty組み込みです。

# Install

Composerで導入可能です。CI4やSmartyも予めComposerでインストールしておいてください。

```bash
$ composer require sarah-systems/ci4smarty
```

# Usage

デフォルトでは、Smarty関連のパスは自体は次のような設定で動作するようになっています。

| 種類         | パス                        |
|:-------------|:----------------------------|
| テンプレート | app/Views                   |
| コンパイル   | writable/smarty/templates_c |
| キャッシュ   | writable/smarty/cache       |
| コンフィグ   | writable/smarty/config      |

`writable`が正しく書き込み可能な場合、下の3つのディレクトリは自動的に生成されます。
もしエラーが起きた場合は、これらのディレクトリを作って適切なパーミッションを与えることで動作します。

もしこのパスを変更したい場合は`.env`ファイルに次のパラメータをセットすることで、任意のパスに変更可能です。

```bash
CI4Smarty.TemplateDir = /path/to/TemplateDir
CI4Smarty.CompileDir = /path/to/CompileDir
CI4Smarty.LeftDelimiter = {{
CI4Smarty.RightDelimiter = }}
CI4Smarty.CacheDir = /path/to/CacheDir
CI4Smarty.ConfigDir = /path/to/ConfigDir
```

これ以外にも`.env`では、SmartyのDebugフラグのOn/Offとデフォルトのテンプレート拡張子を設定できます。

```bash
CI4Smarty.Debug = 1 または 0
CI4Smarty.DefaultTemplateExtension = .tpl
```

### view()
CI4のview関数をSmarty用にCI4Smartyというnamespaceで定義しています。

利用する際は`app`ディレクトリ直下の`Common.php`に次を追記してください。

```php
require_once ROOTPATH . "vendor/sarah-systems/ci4smarty/src/Common.php";
```

使用法はCI4のview関数と同じですが、関数の利用時には名前空間を指定するか、事前にエイリアスを張ってください。

```php
\CI4Smarty\view('template.tpl');
```

または

```php
use function CI4Smarty\view as view;
view('template.tpl');
```


拡張子`.tpl`（`CI4Smarty.DefaultTemplateExtension`で設定されたものです。無指定の場合は`.tpl`）は省略可能です。

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

第3引数の$optionsは無視されます。

### Service

CI4のServiceが利用可能です。

```php
use CI4Smarty\Config\Services;

$time = date('Y-m-d H:i:s');
$smarty = Services::smarty();
$smarty->assign('time',$time);
$smarty->display('template.tpl');
```

# License
The source code is licensed MIT. The website content is licensed CC BY 4.0,see LICENSE.