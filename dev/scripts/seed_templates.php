<?php

declare(strict_types=1);

/**
 * CLI: seed label templates including pack60.
 * Usage: php scripts/seed_templates.php [--force]
 */

require dirname(__DIR__) . '/bootstrap.php';

use App\Services\LabelTemplateService;

$force = in_array('--force', $argv ?? [], true);
$svc = new LabelTemplateService();
$result = $svc->seed($force);
echo json_encode($result, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) . PHP_EOL;
