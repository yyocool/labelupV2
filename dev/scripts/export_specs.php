<?php

declare(strict_types=1);

require dirname(__DIR__) . '/bootstrap.php';

use App\Repositories\ShopRepository;

$out = APP_ROOT . '/storage/imports/specs_export.json';
$rows = (new ShopRepository())->allSpecs();
file_put_contents($out, json_encode($rows, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
echo json_encode(['count' => count($rows), 'path' => $out], JSON_UNESCAPED_UNICODE) . PHP_EOL;
