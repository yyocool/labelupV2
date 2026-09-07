<?php

declare(strict_types=1);

/**
 * Generate 20 high-quality cliparts per real category via OpenAI.
 * Resume-safe: skips files that already exist.
 *
 * Usage:
 *   php scripts/generate_hq_cliparts.php
 *   php scripts/generate_hq_cliparts.php --cats=food,beauty
 */

require dirname(__DIR__) . '/bootstrap.php';

if (function_exists('ob_implicit_flush')) {
    ob_implicit_flush(true);
}

use App\Services\OpenAIService;

$root = dirname(__DIR__);
$catalogPath = $root . '/storage/imports/clipart_hq_catalog.json';
$manifestPath = $root . '/storage/imports/clipart_hq_manifest.json';
$progressPath = $root . '/storage/imports/clipart_hq_progress.json';
$outDir = $root . '/public/assets/cliparts';

$raw = file_get_contents($catalogPath);
$catalog = json_decode((string) $raw, true);
if (!is_array($catalog) || !isset($catalog['categories']) || !is_array($catalog['categories'])) {
    fwrite(STDERR, "Invalid catalog: {$catalogPath}\n");
    exit(1);
}

$style = (string) ($catalog['style'] ?? '');
if (!is_dir($outDir) && !mkdir($outDir, 0777, true) && !is_dir($outDir)) {
    fwrite(STDERR, "Cannot create output dir: {$outDir}\n");
    exit(1);
}

$progress = ['ok' => [], 'fail' => []];
if (is_file($progressPath)) {
    $prev = json_decode((string) file_get_contents($progressPath), true);
    if (is_array($prev)) {
        $progress['ok'] = is_array($prev['ok'] ?? null) ? $prev['ok'] : [];
        $progress['fail'] = is_array($prev['fail'] ?? null) ? $prev['fail'] : [];
    }
}

$onlyCats = [];
$manifestOnly = false;
$writeManifest = true;
foreach ($argv ?? [] as $arg) {
    $arg = (string) $arg;
    if (str_starts_with($arg, '--cats=')) {
        $onlyCats = array_values(array_filter(array_map('trim', explode(',', substr($arg, 7)))));
    } elseif ($arg === '--manifest-only') {
        $manifestOnly = true;
    } elseif ($arg === '--no-manifest') {
        $writeManifest = false;
    }
}

$openai = new OpenAIService();
$items = [];
$sort = 1;
$total = 0;
$done = 0;
$skipped = 0;
$failed = 0;

foreach ($catalog['categories'] as $cat) {
    $slug = (string) ($cat['slug'] ?? '');
    if ($onlyCats !== [] && !in_array($slug, $onlyCats, true)) {
        continue;
    }
    $total += count($cat['items'] ?? []);
}

foreach ($catalog['categories'] as $cat) {
    $slug = (string) ($cat['slug'] ?? '');
    if ($onlyCats !== [] && !in_array($slug, $onlyCats, true)) {
        continue;
    }
    $slug = (string) ($cat['slug'] ?? '');
    $catTags = (string) ($cat['tags'] ?? '');
    $list = is_array($cat['items'] ?? null) ? $cat['items'] : [];
    foreach ($list as $i => $it) {
        $id = (string) ($it['id'] ?? ('item' . ($i + 1)));
        $n = str_pad((string) ($i + 1), 2, '0', STR_PAD_LEFT);
        $file = "hq_{$slug}_{$n}_{$id}.png";
        $rel = '/assets/cliparts/' . $file;
        $full = $outDir . DIRECTORY_SEPARATOR . $file;
        $key = $slug . '/' . $id;

        $row = [
            'title' => (string) ($it['title'] ?? $id),
            'category_slug' => $slug,
            'image_path' => $rel,
            'hashtags' => trim($catTags . ' ' . (string) ($it['tags'] ?? '') . ' #클립아트 #고품질'),
            'description' => ($cat['name'] ?? $slug) . '용 ' . (string) ($it['title'] ?? $id) . ' 클립아트',
            'sort_order' => $sort++,
        ];

        if (is_file($full) && filesize($full) > 8000) {
            $items[] = $row;
            $skipped++;
            $progress['ok'][$key] = $file;
            echo "[skip] {$file}\n";
            continue;
        }
        if ($manifestOnly) {
            continue;
        }

        $prompt = $style . ' Subject: ' . (string) ($it['subject'] ?? $it['title'] ?? $id)
            . ' CRITICAL: perfectly pure white background only, never black, never dark, never gray backdrop.';
        $ok = false;
        $lastError = '';
        for ($try = 1; $try <= 8; $try++) {
            try {
                $result = $openai->generateClipart($prompt);
                $url = (string) ($result['url'] ?? '');
                $srcName = basename((string) (parse_url($url, PHP_URL_PATH) ?: ''));
                $candidates = [
                    public_path('assets/ai-clipart/' . $srcName),
                    $root . '/storage/ai-clipart/' . $srcName,
                ];
                $src = '';
                foreach ($candidates as $cand) {
                    if ($srcName !== '' && is_file($cand)) {
                        $src = $cand;
                        break;
                    }
                }
                if ($src === '') {
                    throw new RuntimeException('generated file not found: ' . $url);
                }
                if (!@copy($src, $full)) {
                    throw new RuntimeException('copy failed to ' . $full);
                }
                @chmod($full, 0666);
                @unlink($src);
                $ok = true;
                break;
            } catch (Throwable $e) {
                $lastError = $e->getMessage();
                echo "[retry {$try}] {$file}: {$lastError}\n";
                $wait = str_contains($lastError, 'Rate limit') ? 20 : (3 * $try);
                sleep($wait);
            }
        }

        if ($ok) {
            $items[] = $row;
            $done++;
            $progress['ok'][$key] = $file;
            unset($progress['fail'][$key]);
            echo "[ok] {$file}\n";
        } else {
            $failed++;
            $progress['fail'][$key] = $lastError;
            echo "[fail] {$file}: {$lastError}\n";
        }

        if ($writeManifest) {
            file_put_contents(
                $progressPath,
                json_encode($progress, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT)
            );
            file_put_contents(
                $manifestPath,
                json_encode(['count' => count($items), 'items' => $items], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT)
            );
        }
    }
}

if ($writeManifest) {
    file_put_contents(
        $manifestPath,
        json_encode(['count' => count($items), 'items' => $items], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT)
    );
    file_put_contents(
        $progressPath,
        json_encode($progress, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT)
    );
}

echo json_encode([
    'total' => $total,
    'generated' => $done,
    'skipped' => $skipped,
    'failed' => $failed,
    'manifest' => $manifestPath,
], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) . PHP_EOL;

if ($failed > 0) {
    exit(2);
}
