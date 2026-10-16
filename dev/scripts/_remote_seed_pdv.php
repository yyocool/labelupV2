<?php
/* PHP 5.2 compatible PDV clipart seeder for PHPS CLI */
error_reporting(E_ALL);
ini_set('display_errors', '1');

$lines = file('/home/uptube1/.env');
$c = array();
foreach ($lines as $l) {
    $l = trim($l);
    if ($l === '' || $l[0] === '#' || strpos($l, '=') === false) {
        continue;
    }
    $p = explode('=', $l, 2);
    $c[trim($p[0])] = trim($p[1], " \t\"'");
}

$mysqli = new mysqli($c['DB_HOST'], $c['DB_USERNAME'], $c['DB_PASSWORD'], $c['DB_DATABASE']);
if ($mysqli->connect_errno) {
    fwrite(STDERR, "db fail\n");
    exit(1);
}
$mysqli->set_charset('utf8');

function pdv_cat_id($mysqli, $slug)
{
    $slug = $mysqli->real_escape_string($slug);
    $res = $mysqli->query("SELECT id FROM clipart_categories WHERE slug='{$slug}' LIMIT 1");
    $row = $res ? $res->fetch_assoc() : null;
    return $row ? (int) $row['id'] : 0;
}

function pdv_ensure_cat($mysqli, $slug, $name)
{
    $id = pdv_cat_id($mysqli, $slug);
    if ($id) {
        return $id;
    }
    $slug_e = $mysqli->real_escape_string($slug);
    $name_e = $mysqli->real_escape_string($name);
    $mysqli->query(
        "INSERT INTO clipart_categories (name, slug, description, sort_order, is_active, created_at, updated_at) " .
        "VALUES ('{$name_e}','{$slug_e}','Public Domain Vectors',200,1,NOW(),NOW())"
    );
    return (int) $mysqli->insert_id;
}

$names = array(
    'animal' => '동물',
    'nature' => '자연·식물',
    'food' => '식품·카페',
    'business' => '비즈니스',
    'shipping' => '배송·물류',
    'traffic' => '교통',
    'character' => '캐릭터',
    'pattern' => '패턴·텍스처',
    'shape' => '기본 도형',
    'kids' => '네임·키즈',
    'beauty' => '뷰티·화장품',
);

$raw = file_get_contents('/home/uptube1/storage/imports/clipart_pdv_manifest.json');
$data = json_decode($raw, true);
$items = (isset($data['items']) && is_array($data['items'])) ? $data['items'] : array();

$inserted = 0;
$updated = 0;
$skipped = 0;

foreach ($items as $it) {
    $path = isset($it['image_path']) ? $it['image_path'] : '';
    if ($path === '' || strpos($path, '/assets/cliparts/pdv_') !== 0) {
        $skipped++;
        continue;
    }
    $full = '/home/uptube1/www' . $path;
    if (!is_file($full)) {
        $skipped++;
        continue;
    }
    $slug = isset($it['category_slug']) ? $it['category_slug'] : 'animal';
    $name = isset($names[$slug]) ? $names[$slug] : $slug;
    $cid = pdv_ensure_cat($mysqli, $slug, $name);
    $title = isset($it['title']) ? $it['title'] : 'clipart';
    $tags = isset($it['hashtags']) ? $it['hashtags'] : '';
    $desc = isset($it['description']) ? $it['description'] : '';
    $sort = isset($it['sort_order']) ? (int) $it['sort_order'] : 0;

    $path_e = $mysqli->real_escape_string($path);
    $title_e = $mysqli->real_escape_string($title);
    $tags_e = $mysqli->real_escape_string($tags);
    $desc_e = $mysqli->real_escape_string($desc);

    $res = $mysqli->query("SELECT id FROM cliparts WHERE image_path='{$path_e}' LIMIT 1");
    $row = $res ? $res->fetch_assoc() : null;
    if ($row) {
        $id = (int) $row['id'];
        $mysqli->query(
            "UPDATE cliparts SET category_id={$cid}, title='{$title_e}', hashtags='{$tags_e}', " .
            "description='{$desc_e}', sort_order={$sort}, is_active=1, source='seed', updated_at=NOW() WHERE id={$id}"
        );
        $updated++;
    } else {
        $mysqli->query(
            "INSERT INTO cliparts (category_id, title, image_path, hashtags, description, sort_order, is_active, source, created_at, updated_at) " .
            "VALUES ({$cid},'{$title_e}','{$path_e}','{$tags_e}','{$desc_e}',{$sort},1,'seed',NOW(),NOW())"
        );
        $inserted++;
    }
}

echo 'inserted=' . $inserted . ' updated=' . $updated . ' skipped=' . $skipped . ' items=' . count($items) . "\n";
