<?php

use CI4Smarty\Config\Services;
use CI4Smarty\Debug\Toolbar\Collectors\SmartyViews;
use CodeIgniter\Debug\Toolbar\Collectors\Views;

use function CI4Smarty\clear_view_cache;
use function CI4Smarty\view;

$root = dirname(__DIR__) . DIRECTORY_SEPARATOR;
$framework = $root . 'vendor/codeigniter4/framework/';
$fixture = sys_get_temp_dir() . '/ci4smarty-' . bin2hex(random_bytes(8)) . '/';
mkdir($fixture, 0700);
mkdir($fixture . 'cache');
mkdir($fixture . 'logs');

register_shutdown_function(static function () use ($fixture): void {
    $entries = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($fixture, FilesystemIterator::SKIP_DOTS),
        RecursiveIteratorIterator::CHILD_FIRST
    );
    foreach ($entries as $entry) {
        if ($entry->isDir()) {
            rmdir($entry->getPathname());
        } else {
            unlink($entry->getPathname());
        }
    }
    rmdir($fixture);
});

define('HOMEPATH', $root);
define('CONFIGPATH', $framework . 'app/Config/');
define('PUBLICPATH', $framework . 'public/');
define('WRITEPATH', $fixture);
define('CI_DEBUG', true);

require $root . 'vendor/autoload.php';
require $framework . 'system/Test/bootstrap.php';
require $root . 'src/Common.php';

function expectSame($expected, $actual, string $case): void
{
    if ($expected !== $actual) {
        throw new RuntimeException(
            $case . ' expected ' . var_export($expected, true) . ', got ' . var_export($actual, true)
        );
    }
}

$_ENV['CI4Smarty.TemplateDir'] = $fixture;
$_ENV['CI4Smarty.Debug'] = 'false';
file_put_contents($fixture . 'message.tpl', '{$CI.left|default:"-"}/{$CI.right|default:"-"}/{$shared}');
file_put_contents($fixture . 'value.tpl', '{$CI}');
file_put_contents($fixture . 'custom.html', 'custom');
file_put_contents($fixture . 'empty.tpl', '');

$cacheConfig = config('Cache');
$cacheConfig->handler = 'file';
$cacheConfig->storePath = $fixture;
if (property_exists($cacheConfig, 'file')) {
    $cacheConfig->file['storePath'] = $fixture;
}

$toolbar = config('Toolbar');
$toolbar->collectors = [Views::class];

$smarty = Services::smarty();
expectSame($smarty, Services::smarty(), 'Shared Smarty service');
$smarty->assign('shared', 'S');
$smarty->assign('CI', 'direct');
expectSame(0, (int) $smarty->debugging, 'String false disables Smarty debug');

$quiet = ['debug' => false];
$config = config('View');
$config->saveData = false;
expectSame('A/-/S', view('message', ['left' => 'A'], $quiet), 'Implicit extension');
expectSame('-/B/S', view('message.tpl', ['right' => 'B'], $quiet), 'Explicit extension and config false');

$_ENV['CI4Smarty.DefaultTemplateExtension'] = '.html';
expectSame('custom', view('custom', [], $quiet), 'Custom extension');
$_ENV['CI4Smarty.DefaultTemplateExtension'] = '';
expectSame('custom', view('custom.html', [], $quiet), 'Empty extension');
unset($_ENV['CI4Smarty.DefaultTemplateExtension']);

$config->saveData = true;
expectSame('A/-/S', view('message', ['left' => 'A'], $quiet), 'Config true');
expectSame('X/B/S', view('message', ['left' => 'X', 'right' => 'B'], $quiet + ['saveData' => false]), 'Call override false');
expectSame('A/-/S', view('message', [], $quiet), 'Transient data discarded');

try {
    view('missing', ['left' => 'failed'], $quiet);
    throw new RuntimeException('Missing template did not throw');
} catch (\Smarty\Exception $exception) {
    expectSame('A/-/S', view('message', [], $quiet), 'Failed render preserves previously retained data');
}
expectSame('direct', $smarty->getTemplateVars('CI'), 'Shared Smarty CI assignment');
expectSame('scalar', view('value', 'scalar', $quiet), 'Mixed scalar input');
expectSame('-/C/S', view('message', ['right' => 'C'], $quiet), 'Array replaces saved scalar');

$config->saveData = false;
$cached = ['cache' => 30, 'cache_name' => 'integration'];
Services::cache()->save('integration', 'standard-cache-entry', 30);
expectSame('D/C/S', view('message', ['left' => 'D'], $quiet + $cached), 'Cache miss');
$marked = view('message', ['left' => 'E'], $cached + ['saveData' => true]);
expectSame(
    '<!-- DEBUG-VIEW START message.tpl -->' . PHP_EOL . 'D/C/S' . PHP_EOL
        . '<!-- DEBUG-VIEW ENDED message.tpl -->' . PHP_EOL,
    $marked,
    'Cache hit uses the current debug option'
);
expectSame('E/C/S', view('message', [], $quiet), 'Cache hit saves current data');
expectSame('D/C/S', view('message', [], $quiet + $cached), 'Cached HTML excludes debug markers');
expectSame('standard-cache-entry', Services::cache()->get('integration'), 'Standard cache key stays separate');
expectSame('F/C/S', view('message', ['left' => 'F'], $quiet + ['cache_name' => 'integration']), 'Cache name alone');

$defaultCache = $quiet + ['cache' => 30];
expectSame('G/C/S', view('message', ['left' => 'G'], $defaultCache), 'Default cache key');
expectSame('G/C/S', view('message.tpl', ['left' => 'H'], $defaultCache), 'Equivalent names share the default cache key');
expectSame('', view('empty', [], $quiet + ['cache' => 30, 'cache_name' => 'empty']), 'Empty HTML cache miss');
expectSame('', view('missing', [], $quiet + ['cache' => 30, 'cache_name' => 'empty']), 'Empty HTML cache hit');

$shortCache = $quiet + ['cache' => 1, 'cache_name' => 'expiry'];
expectSame('I/C/S', view('message', ['left' => 'I'], $shortCache), 'Short-lived cache write');
sleep(2);
expectSame('J/C/S', view('message', ['left' => 'J'], $shortCache), 'Expired cache rerenders');

$toolbar->collectors = [];
expectSame('E/C/S', view('message'), 'Disabled Views collector');
$standardViewDir = $fixture . 'standard/';
mkdir($standardViewDir);
file_put_contents($standardViewDir . 'example.php', '<?php echo $content;');
\Config\Services::renderer($standardViewDir);
expectSame('native', \view('example', ['content' => 'native'], ['debug' => false]), 'Standard CI4 view');


$legacyKey = 'ci4smarty_' . hash('sha256', 'value.tpl');
Services::cache()->save($legacyKey, 'legacy output', 30);
expectSame('legacy output', view('value', 'present', $defaultCache), '0.2.x cache key is still read');
expectSame(true, clear_view_cache('value.tpl'), 'Delete an existing default key');
expectSame('present', view('value', 'present', $defaultCache), 'Default cache is rebuilt');
expectSame(false, clear_view_cache('file-does-not-exist'), 'Missing cache returns the handler result');
expectSame(true, clear_view_cache('file-does-not-exist', 'integration'), 'Delete custom key without locating the view');
expectSame('N/C/S', view('message', ['left' => 'N'], $quiet + $cached), 'Custom cache is rebuilt');
expectSame('G/C/S', view('message', [], $defaultCache), 'A different CI4Smarty key survives');
expectSame('standard-cache-entry', Services::cache()->get('integration'), 'A standard CI4 key survives');

$collector = new SmartyViews();
expectSame([], $collector->timelineData(), 'Timeline stays empty unless its collector is enabled');
$toolbar->collectors = [SmartyViews::class];
$timelineCache = ['cache' => 30, 'cache_name' => 'timeline'];
expectSame('first', view('value', 'first', $timelineCache), 'Dedicated collector adds no HTML markers');
expectSame('first', view('value', 'second', $timelineCache), 'Recorded cache hit');
view('value', 'quiet', $quiet);
$entries = $collector->timelineData();
expectSame(2, count($entries), 'Timeline records only eligible calls');
expectSame('Smarty view: value.tpl', $entries[0]['name'], 'Rendered view timeline label');
expectSame('Smarty view: value.tpl (cache hit)', $entries[1]['name'], 'Cache hit timeline label');
expectSame('Smarty Views', $entries[0]['component'], 'Timeline component');
expectSame(true, $entries[0]['duration'] >= 0 && $entries[0]['start'] > 0, 'Timeline records elapsed time');
$toolbar->collectors = [];
view('value', 'disabled');
expectSame(2, count($collector->timelineData()), 'Unregistered collector records no further views');

$vendor = 'CI4SmartyFixture' . bin2hex(random_bytes(5));
$module = $vendor . '\\Blog';
$moduleFirst = $fixture . 'module-first';
$moduleSecond = $fixture . 'module-second';
$parentModule = $fixture . 'module-parent';
mkdir($moduleFirst . '/Views', 0700, true);
mkdir($moduleSecond . '/Views/posts', 0700, true);
mkdir($parentModule . '/Views/Blog/posts', 0700, true);
Services::autoloader()->addNamespace([
    $module => [$moduleFirst, $moduleSecond],
    $vendor => $parentModule,
]);
file_put_contents($moduleFirst . '/Views/ordered.tpl', 'first directory');
file_put_contents($moduleSecond . '/Views/ordered.tpl', 'second directory');
file_put_contents($parentModule . '/Views/Blog/posts/article.tpl', 'shorter namespace');
file_put_contents($moduleSecond . '/Views/posts/article.tpl', '{extends file="./layout.tpl"}{block name="body"}{include file="./part.tpl"}{/block}');
file_put_contents($moduleSecond . '/Views/posts/layout.tpl', '[{block name="body"}{/block}]');
file_put_contents($moduleSecond . '/Views/posts/part.tpl', '{$CI.name}');
expectSame('first directory', view('module:' . $module . '\\ordered', [], $quiet), 'Namespace paths are searched in order');
$article = 'module:' . $module . '\\posts/article';
expectSame('[module]', view($article, ['name' => 'module'], $quiet), 'Longest namespace and relative include and extends');

$overridesRoot = APPPATH . 'Views/overrides';
$createdOverridesRoot = ! is_dir($overridesRoot);
$overrideVendor = $overridesRoot . '/' . $vendor;
$overrideDirectory = $overrideVendor . '/Blog/posts';
mkdir($overrideDirectory, 0700, true);
$overrideFile = $overrideDirectory . '/article.tpl';
register_shutdown_function(static function () use ($overrideFile, $overrideDirectory, $overrideVendor, $overridesRoot, $createdOverridesRoot): void {
    if (is_file($overrideFile)) {
        unlink($overrideFile);
    }
    rmdir($overrideDirectory);
    rmdir(dirname($overrideDirectory));
    rmdir($overrideVendor);
    if ($createdOverridesRoot) {
        @rmdir($overridesRoot);
    }
});
file_put_contents($overrideFile, 'override {$CI.name}');
expectSame('override app', view($article . '.tpl', ['name' => 'app'], $quiet), 'App override takes precedence');
foreach ([
    'module:NoSuchNamespace\\Blog\\article' => 'No CI4 namespace registered',
    'module:' . $module . '\\missing' => 'Smarty module template not found',
    'module:' . $module . '\\../article' => 'Invalid CI4Smarty module template',
] as $badView => $expectedMessage) {
    try {
        view($badView, [], $quiet);
        throw new RuntimeException('Expected an error for ' . $badView);
    } catch (\Smarty\Exception $exception) {
        expectSame(true, strpos($exception->getMessage(), $expectedMessage) === 0, 'Module lookup error: ' . $badView);
    }
}

unset($_ENV['CI4Smarty.TemplateDir'], $_ENV['CI4Smarty.Debug']);
$subclass = new class ($fixture) extends \CI4Smarty\ThirdParty\CiSmarty {
    public function __construct(string $directory)
    {
        $this->TemplateDir = $directory;
        parent::__construct();
    }
};
expectSame('custom', $subclass->fetch('custom.html'), 'Legacy protected template directory default');

file_put_contents($fixture . 'SmartyConfig.php', <<<'PHP'
<?php
namespace Config;

class Smarty extends \CI4Smarty\Config\Smarty
{
    public $defaultTemplateExtension = '.view';
}
PHP
);
require $fixture . 'SmartyConfig.php';
$appSmarty = config(\Config\Smarty::class);
mkdir($fixture . 'secondary');
$appSmarty->templateDir = [$fixture, $fixture . 'secondary'];
$appSmarty->compileDir = $fixture . 'compiled';
$appSmarty->cacheDir = $fixture . 'smarty-cache';
$appSmarty->configDir = $fixture . 'smarty-config';
$appSmarty->leftDelimiter = '{{';
$appSmarty->rightDelimiter = '}}';
$appSmarty->debug = true;
file_put_contents($fixture . 'configured.view', 'extension from app config');
file_put_contents($fixture . 'secondary/secondary.view', 'second search directory');

expectSame('extension from app config', view('configured', [], $quiet), 'Configured extension is applied to rendering');
$_ENV['CI4Smarty.DefaultTemplateExtension'] = '.html';
expectSame('custom', view('custom', [], $quiet), 'Legacy environment extension overrides app config');
unset($_ENV['CI4Smarty.DefaultTemplateExtension']);

$configuredSmarty = Services::smarty(false);
expectSame('{{', $configuredSmarty->getLeftDelimiter(), 'Left delimiter from app config');
expectSame('}}', $configuredSmarty->getRightDelimiter(), 'Right delimiter from app config');
expectSame(true, (bool) $configuredSmarty->debugging, 'Debug from app config');
foreach ([
    $configuredSmarty->getCompileDir() => 'compiled',
    $configuredSmarty->getCacheDir() => 'smarty-cache',
    $configuredSmarty->getConfigDir()[0] => 'smarty-config',
] as $configuredDir => $directoryName) {
    expectSame($directoryName, basename(rtrim($configuredDir, '\\/')), 'Configured Smarty directory');
}
$_ENV['CI4Smarty.Debug'] = 'false';
$configuredSmarty = Services::smarty(false);
expectSame(false, (bool) $configuredSmarty->debugging, 'Legacy environment debug overrides app config');
expectSame('second search directory', $configuredSmarty->fetch('secondary.view'), 'Configured template directory order');
$_ENV['CI4Smarty.TemplateDir'] = $fixture;
expectSame(1, count(Services::smarty(false)->getTemplateDir()), 'Legacy environment path overrides the array');
unset($_ENV['CI4Smarty.TemplateDir'], $_ENV['CI4Smarty.Debug']);

echo 'CI4Smarty integration checks passed on PHP ' . PHP_VERSION . PHP_EOL;
