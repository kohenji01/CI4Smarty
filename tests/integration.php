<?php

use CI4Smarty\Config\Services;
use CodeIgniter\Debug\Toolbar\Collectors\Views;

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

echo 'CI4Smarty integration checks passed on PHP ' . PHP_VERSION . PHP_EOL;
