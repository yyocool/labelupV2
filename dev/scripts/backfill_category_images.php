<?php

declare(strict_types=1);

require dirname(__DIR__) . '/bootstrap.php';

use App\Repositories\ShopRepository;

$repo = new ShopRepository();
$updated = 0;

foreach ($repo->allCategories() as $category) {
    $slug = trim((string) ($category['slug'] ?? ''));
    if ($slug === '') {
        continue;
    }

    $path = '/assets/categories/cat_' . $slug . '.webp';
    $full = public_path(ltrim($path, '/'));
    if (!is_file($full)) {
        continue;
    }

    $repo->saveCategory([
        'id' => (int) $category['id'],
        'name' => (string) $category['name'],
        'slug' => $slug,
        'sort_order' => (int) ($category['sort_order'] ?? 0),
        'is_active' => !empty($category['is_active']),
        'image_path' => $path,
    ]);
    $updated++;
}

echo json_encode(['updated' => $updated], JSON_UNESCAPED_UNICODE) . PHP_EOL;
