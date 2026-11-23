# 変更履歴

日本語 | [English](CHANGELOG.en.md)

## 0.2.1

- 英語版 README と変更履歴を追加しました。

## 0.2.0

- Smarty 5.9 以降の 5 系に対応。Smarty 3／4 から更新する場合は、テンプレートと独自プラグインを確認してください。[Smarty の移行ガイド](https://smarty-php.github.io/smarty/5.x/upgrading/)
- PHP 7.2〜8.5 と CI4 4.0〜4.7 系の組み合わせを結合テストで検証しました。実行した組み合わせは [README の対応バージョン](README.md#対応バージョン) を参照してください。
- `\CI4Smarty\view()` の第3引数に `saveData`、`cache`、`cache_name`、`debug` を追加しました。設定済みの拡張子を明示したテンプレート名もそのまま使えます。

### 更新時の注意

- `saveData` の省略時は `app/Config/View.php` の `$saveData` に従います。`['saveData' => false]` を渡した描画でも、それ以前に保持されたデータは参照できます。各呼び出しで渡したデータだけを使う運用では、最初からビュー設定の `$saveData` を `false` にしてください。この設定は CI4 の標準ビューにも適用されます。
- HTML キャッシュを有効にすると、既定では同じテンプレート名の描画結果が再利用されます。言語などで公開内容が変わる場合は `cache_name` を分けてください。利用者固有のページや CSRF トークンを含むフォームでは、`cache` を指定せずに描画してください。
