# Smarty for CodeIgniter4

日本語 | [English](README.en.md)

CodeIgniter4用のSmarty組み込みです。

# Install

CodeIgniter 4 のアプリで、Composer から導入できます。

```bash
$ composer require sarah-systems/ci4smarty
```

## 最初の描画

1. `app/Common.php` に、Smarty 用の `view()` を読み込む処理を追記します。

   ```php
   require_once ROOTPATH . 'vendor/sarah-systems/ci4smarty/src/Common.php';
   ```

2. `app/Views/hello.tpl` を作成します。

   ```smarty
   <p>こんにちは、{$CI.name|escape}さん。</p>
   ```

3. 既存のルートに対応するコントローラーのメソッドで、描画結果を返します。

   ```php
   public function index(): string
   {
       return \CI4Smarty\view('hello', ['name' => '太郎']);
   }
   ```

ブラウザーでそのルートを開くと「こんにちは、太郎さん。」と表示されます。

# Usage

デフォルトでは、Smarty関連のパスは自体は次のような設定で動作するようになっています。

| 種類         | パス                        |
|:-------------|:----------------------------|
| テンプレート | app/Views                   |
| コンパイル   | writable/smarty/templates_c |
| キャッシュ   | writable/smarty/cache       |
| コンフィグ   | writable/smarty/configs     |

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
CI4Smarty.Debug = false
CI4Smarty.DefaultTemplateExtension = .tpl
```

### view()
CI4のview関数をSmarty用にCI4Smartyというnamespaceで定義しています。

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

### 描画オプション

```php
echo \CI4Smarty\view('article', ['title' => 'お知らせ'], [
    'saveData'   => false,
    'cache'      => 60,
    'cache_name' => 'article-ja',
    'debug'      => false,
]);
```

| オプション | 動作 |
| :--- | :--- |
| `saveData` | 省略時は `app/Config/View.php` の `$saveData`（取得できない場合は `true`）に従います。配列は保持済みの値に今回の値をマージします。`false` を指定すると、今回の追加分は次回へ持ち越されません。それ以前に保持されたデータは今回の描画でも参照できます。 |
| `cache` | 正の整数を指定すると、描画した HTML を CI4 のキャッシュサービスでその秒数だけ保存します。 |
| `cache_name` | `cache` が有効な場合に使用するキャッシュ名です。省略時は拡張子を補ったテンプレート名を使います。 |
| `debug` | CI4 のデバッグと Views コレクターが有効な場合に付く HTML マーカーを、`false` で抑制できます。Smarty のデバッグコンソールは `CI4Smarty.Debug` で設定します。 |

出力の保存先は CI4 のキャッシュ設定（通常は `app/Config/Cache.php`）で指定します。`CI4Smarty.CacheDir` は Smarty 自身のキャッシュディレクトリを設定します。

同じテンプレートでも言語などで出力が変わる場合は、同じ HTML を共有できる単位で `cache_name` を変えてください。利用者固有のページや CSRF トークンを含むフォームは、キャッシュを有効にせず描画してください。

### 0.1.x からの移行

CI4 アプリのルートで Composer の依存条件を更新します。

```bash
composer require sarah-systems/ci4smarty:^0.2 --with-all-dependencies
```

- Smarty 5.9 以降の 5 系が必要です。Smarty 3／4 から更新する場合は、テンプレートと独自プラグインを [Smarty の移行ガイド](https://smarty-php.github.io/smarty/5.x/upgrading/) に沿って確認してください。
- CI4 の `$saveData` が `true` の場合、同一リクエスト内で前の描画データが引き継がれます。各呼び出しで渡したデータだけを使って描画する運用では `app/Config/View.php` の `$saveData` を `false` にしてください。この設定は CI4 の標準ビューにも適用されます。今回の追加分だけを保持対象から外す場合は、呼び出し時に `['saveData' => false]` を指定できます。
- `CI4Smarty.DefaultTemplateExtension` に空文字を設定すると拡張子の補完を無効にできます。`CI4Smarty.Debug` は `0`／`1` と `false`／`true` を受け付けます。

## 対応バージョン

Composer の依存条件は PHP `^7.2 || ^8.0`、CodeIgniter `^4.0`、Smarty `^5.9` です。PHP 8.5 では CI4 4.7 以降が必要です。使用する PHP と CI4 の組み合わせは [CI4 の動作要件](https://codeigniter.com/user_guide/intro/requirements.html) も確認してください。

以下は Windows で結合テストに合格した組み合わせです。対応するバージョン系列を [GitHub Actions の互換性テスト](https://github.com/kohenji01/CI4Smarty/actions/workflows/compatibility.yml) でも検証する構成にしています。

| PHP | CI4 | Smarty |
| :--- | :--- | :--- |
| 7.2.34 | 4.0.0 | 5.9.0 |
| 7.3.33 | 4.0.5、4.1.9 | 5.9.0 |
| 7.4.33 | 4.2.12 | 5.9.0 |
| 8.0.30 | 4.3.8 | 5.9.0 |
| 8.1.34 | 4.5.8 | 5.9.0 |
| 8.2.29 | 4.7.6 | 5.9.0 |
| 8.3.28 | 4.4.8 | 5.9.0 |
| 8.4.15 | 4.6.5 | 5.9.0 |
| 8.5.12 | 4.7.6 | 5.9.0 |

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