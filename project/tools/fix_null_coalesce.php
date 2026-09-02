<?php
/**
 * PHP 5.6 호환: ?? 연산자를 isset() 삼항 연산자로 변환 (1회 실행용)
 */
$root = dirname(__DIR__);
$iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root));

foreach ($iterator as $file) {
    if ($file->getExtension() !== 'php') {
        continue;
    }
    $path = $file->getPathname();
    if (strpos($path, 'fix_null_coalesce.php') !== false) {
        continue;
    }

    $content = file_get_contents($path);
    if (strpos($content, '??') === false) {
        continue;
    }

    $patterns = array(
        '/(\$[a-zA-Z_][a-zA-Z0-9_]*(?:\[[^\]]+\])+)\s*\?\?\s*/',
        '/(\$[a-zA-Z_][a-zA-Z0-9_]*)\s*\?\?\s*/',
    );

    $new = $content;
    foreach ($patterns as $pattern) {
        $new = preg_replace_callback($pattern, function ($m) {
            return 'isset(' . $m[1] . ') ? ' . $m[1] . ' : ';
        }, $new);
    }

    if ($new !== $content) {
        file_put_contents($path, $new);
        echo str_replace($root . DIRECTORY_SEPARATOR, '', $path) . PHP_EOL;
    }
}

echo "done" . PHP_EOL;
