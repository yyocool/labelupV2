<?php



declare(strict_types=1);



namespace App\Repositories;

use App\Models\BaseModel;
use App\Services\ShopProductImageService;



final class ShopRepository extends BaseModel

{

    public function dashboardStats(): array

    {

        $products = $this->fetchOne('SELECT COUNT(*) AS cnt FROM shop_products WHERE status = :s', ['s' => 'active']);

        $ordersToday = $this->fetchOne(

            'SELECT COUNT(*) AS cnt, COALESCE(SUM(total_amount),0) AS revenue FROM shop_orders WHERE created_at >= :today',

            ['today' => date('Y-m-d 00:00:00')]

        );

        $pending = $this->fetchOne(

            'SELECT COUNT(*) AS cnt FROM shop_orders WHERE status IN (\'paid\',\'preparing\')'

        );

        $shipping = $this->fetchOne(

            'SELECT COUNT(*) AS cnt FROM shop_orders WHERE status = \'shipping\''

        );



        return [

            'active_products' => (int) ($products['cnt'] ?? 0),

            'orders_today' => (int) ($ordersToday['cnt'] ?? 0),

            'revenue_today' => (int) ($ordersToday['revenue'] ?? 0),

            'pending_orders' => (int) ($pending['cnt'] ?? 0),

            'shipping_orders' => (int) ($shipping['cnt'] ?? 0),

        ];

    }



    /** @return array<int, array<string, mixed>> */

    public function allCategories(): array

    {

        return $this->fetchAll('SELECT * FROM shop_categories ORDER BY sort_order ASC, id ASC');

    }



    public function saveCategory(array $data): int

    {

        $now = date('Y-m-d H:i:s');

        $id = (int) ($data['id'] ?? 0);

        if ($id > 0) {

            $this->execute(

                'UPDATE shop_categories SET name=:name, slug=:slug, image_path=:image_path, sort_order=:sort_order, is_active=:is_active, updated_at=:now WHERE id=:id',

                [

                    'name' => $data['name'],

                    'slug' => $data['slug'],

                    'image_path' => $data['image_path'] ?? null,

                    'sort_order' => (int) $data['sort_order'],

                    'is_active' => (int) !empty($data['is_active']),

                    'now' => $now,

                    'id' => $id,

                ]

            );

            return $id;

        }

        $this->execute(

            'INSERT INTO shop_categories (name, slug, image_path, sort_order, is_active, created_at, updated_at) VALUES (:name,:slug,:image_path,:sort_order,:is_active,:created_at,:updated_at)',

            [

                'name' => $data['name'],

                'slug' => $data['slug'],

                'image_path' => $data['image_path'] ?? null,

                'sort_order' => (int) $data['sort_order'],

                'is_active' => (int) !empty($data['is_active']),

                'created_at' => $now,

                'updated_at' => $now,

            ]

        );

        return (int) $this->lastInsertId();

    }



    public function deleteCategory(int $id): void

    {

        $this->execute('DELETE FROM shop_categories WHERE id = :id', ['id' => $id]);

    }

    public function findCategoryBySlug(string $slug): ?array
    {
        return $this->fetchOne('SELECT * FROM shop_categories WHERE slug = :slug LIMIT 1', ['slug' => $slug]);
    }

    public function findProductBySku(string $sku): ?array
    {
        return $this->fetchOne('SELECT * FROM shop_products WHERE sku = :sku LIMIT 1', ['sku' => $sku]);
    }

    public function findSpecMatch(float $width, float $height, string $material, ?int $labels): ?array
    {
        if ($labels === null) {
            return $this->fetchOne(
                'SELECT * FROM label_specs WHERE width_mm = :w AND height_mm = :h AND material = :m AND labels_per_sheet IS NULL LIMIT 1',
                [
                    'w' => $width,
                    'h' => $height,
                    'm' => $material,
                ]
            );
        }

        return $this->fetchOne(
            'SELECT * FROM label_specs WHERE width_mm = :w AND height_mm = :h AND material = :m AND labels_per_sheet = :l LIMIT 1',
            [
                'w' => $width,
                'h' => $height,
                'm' => $material,
                'l' => $labels,
            ]
        );
    }



    /** @return array<int, array<string, mixed>> */

    public function allSpecs(): array

    {

        return $this->fetchAll('SELECT * FROM label_specs ORDER BY id DESC');

    }



    public function saveSpec(array $data): int

    {

        $now = date('Y-m-d H:i:s');

        $id = (int) ($data['id'] ?? 0);

        $params = [

            'name' => $data['name'],

            'image_path' => $data['image_path'] ?? null,

            'width_mm' => $data['width_mm'],

            'height_mm' => $data['height_mm'],

            'material' => $data['material'] ?? '',

            'shape' => $data['shape'] ?? 'rect',

            'labels_per_sheet' => $data['labels_per_sheet'] !== null && $data['labels_per_sheet'] !== '' ? (int) $data['labels_per_sheet'] : null,

            'description' => $data['description'] ?? '',

            'is_active' => (int) !empty($data['is_active']),

            'now' => $now,

        ];

        if ($id > 0) {

            $this->execute(

                'UPDATE label_specs SET name=:name,image_path=:image_path,width_mm=:width_mm,height_mm=:height_mm,material=:material,shape=:shape,labels_per_sheet=:labels_per_sheet,description=:description,is_active=:is_active,updated_at=:now WHERE id=:id',

                $params + ['id' => $id]

            );

            return $id;

        }

        $insertParams = [
            'name' => $params['name'],
            'image_path' => $data['image_path'] ?? null,
            'width_mm' => $params['width_mm'],
            'height_mm' => $params['height_mm'],
            'material' => $params['material'],
            'shape' => $params['shape'],
            'labels_per_sheet' => $params['labels_per_sheet'],
            'description' => $params['description'],
            'is_active' => $params['is_active'],
            'created_at' => $now,
            'updated_at' => $now,
        ];

        $this->execute(

            'INSERT INTO label_specs (name,image_path,width_mm,height_mm,material,shape,labels_per_sheet,description,is_active,created_at,updated_at) VALUES (:name,:image_path,:width_mm,:height_mm,:material,:shape,:labels_per_sheet,:description,:is_active,:created_at,:updated_at)',

            $insertParams

        );

        return (int) $this->lastInsertId();

    }



    public function deleteSpec(int $id): void

    {

        $this->execute('DELETE FROM label_specs WHERE id = :id', ['id' => $id]);

    }



    /** @return array<int, array<string, mixed>> */

    public function allProducts(): array

    {

        return $this->fetchAll(

            'SELECT p.*, c.name AS category_name, s.name AS spec_name

             FROM shop_products p

             LEFT JOIN shop_categories c ON c.id = p.category_id

             LEFT JOIN label_specs s ON s.id = p.spec_id

             ORDER BY p.sort_order ASC, p.id DESC'

        );

    }



    public function saveProduct(array $data): int

    {

        $now = date('Y-m-d H:i:s');

        $id = (int) ($data['id'] ?? 0);

        $params = [

            'category_id' => (int) $data['category_id'],

            'spec_id' => !empty($data['spec_id']) ? (int) $data['spec_id'] : null,

            'name' => $data['name'],

            'sku' => $data['sku'],

            'price' => (int) $data['price'],

            'sale_price' => $data['sale_price'] !== null && $data['sale_price'] !== '' ? (int) $data['sale_price'] : null,

            'stock_qty' => (int) $data['stock_qty'],

            'status' => $data['status'] ?? 'draft',

            'description' => $data['description'] ?? '',

            'meta_json' => $this->encodeMetaJson($data['meta_json'] ?? null),

            'sort_order' => (int) ($data['sort_order'] ?? 0),

            'thumbnail' => $data['thumbnail'] ?? null,

            'now' => $now,

        ];

        if ($id > 0) {

            $this->execute(

                'UPDATE shop_products SET category_id=:category_id,spec_id=:spec_id,name=:name,sku=:sku,price=:price,sale_price=:sale_price,stock_qty=:stock_qty,status=:status,description=:description,meta_json=:meta_json,sort_order=:sort_order,thumbnail=:thumbnail,updated_at=:now WHERE id=:id',

                $params + ['id' => $id]

            );

            return $id;

        }

        $insertParams = [
            'category_id' => $params['category_id'],
            'spec_id' => $params['spec_id'],
            'name' => $params['name'],
            'sku' => $params['sku'],
            'price' => $params['price'],
            'sale_price' => $params['sale_price'],
            'stock_qty' => $params['stock_qty'],
            'status' => $params['status'],
            'description' => $params['description'],
            'meta_json' => $params['meta_json'],
            'sort_order' => $params['sort_order'],
            'thumbnail' => $params['thumbnail'],
            'created_at' => $now,
            'updated_at' => $now,
        ];

        $this->execute(

            'INSERT INTO shop_products (category_id,spec_id,name,sku,price,sale_price,stock_qty,status,description,meta_json,sort_order,thumbnail,created_at,updated_at) VALUES (:category_id,:spec_id,:name,:sku,:price,:sale_price,:stock_qty,:status,:description,:meta_json,:sort_order,:thumbnail,:created_at,:updated_at)',

            $insertParams

        );

        return (int) $this->lastInsertId();

    }



    public function deleteProduct(int $id): void

    {

        $this->execute('DELETE FROM shop_products WHERE id = :id', ['id' => $id]);

    }



    /** @return array{items: array<int, array<string, mixed>>, total: int, page: int, pages: int} */

    public function paginateOrders(string $status = '', int $page = 1, int $perPage = 20): array

    {

        $page = max(1, $page);

        $total = $this->countOrders($status);

        $pages = max(1, (int) ceil($total / $perPage));

        $where = '1=1';

        $params = [];

        if ($status !== '') {

            $where .= ' AND status = :status';

            $params['status'] = $status;

        }

        $offset = ($page - 1) * $perPage;

        $items = $this->fetchAll(

            "SELECT * FROM shop_orders WHERE {$where} ORDER BY id DESC LIMIT {$perPage} OFFSET {$offset}",

            $params

        );

        return compact('items', 'total', 'page', 'pages') + ['per_page' => $perPage];

    }



    public function countOrders(string $status = ''): int

    {

        if ($status === '') {

            $row = $this->fetchOne('SELECT COUNT(*) AS cnt FROM shop_orders');

        } else {

            $row = $this->fetchOne('SELECT COUNT(*) AS cnt FROM shop_orders WHERE status = :status', ['status' => $status]);

        }

        return (int) ($row['cnt'] ?? 0);

    }



    /** @return array<int, array<string, mixed>> */

    public function orderItems(int $orderId): array

    {

        return $this->fetchAll('SELECT * FROM shop_order_items WHERE order_id = :id ORDER BY id ASC', ['id' => $orderId]);

    }



    public function updateOrder(int $id, array $data): void

    {

        $this->execute(

            'UPDATE shop_orders SET status=:status,payment_status=:payment_status,admin_memo=:admin_memo,carrier=:carrier,tracking_no=:tracking_no,updated_at=:now WHERE id=:id',

            [

                'status' => $data['status'],

                'payment_status' => $data['payment_status'],

                'admin_memo' => $data['admin_memo'] ?? '',

                'carrier' => $data['carrier'] ?? '',

                'tracking_no' => $data['tracking_no'] ?? '',

                'now' => date('Y-m-d H:i:s'),

                'id' => $id,

            ]

        );

    }



    /** @return array<int, array<string, mixed>> */

    public function shippingOrders(): array

    {

        return $this->fetchAll(

            "SELECT * FROM shop_orders WHERE status IN ('paid','preparing','shipping') ORDER BY FIELD(status,'preparing','paid','shipping'), id DESC"

        );

    }



    /** @return array<int, array<string, mixed>> */

    public function allCoupons(): array

    {

        return $this->fetchAll('SELECT * FROM shop_coupons ORDER BY id DESC');

    }



    public function saveCoupon(array $data): int

    {

        $now = date('Y-m-d H:i:s');

        $id = (int) ($data['id'] ?? 0);

        $params = [

            'code' => strtoupper(trim($data['code'])),

            'name' => $data['name'],

            'discount_type' => $data['discount_type'] ?? 'fixed',

            'discount_value' => (int) $data['discount_value'],

            'min_order_amount' => (int) ($data['min_order_amount'] ?? 0),

            'max_uses' => $data['max_uses'] !== null && $data['max_uses'] !== '' ? (int) $data['max_uses'] : null,

            'starts_at' => $data['starts_at'] ?: null,

            'ends_at' => $data['ends_at'] ?: null,

            'is_active' => (int) !empty($data['is_active']),

            'now' => $now,

        ];

        if ($id > 0) {

            $this->execute(

                'UPDATE shop_coupons SET code=:code,name=:name,discount_type=:discount_type,discount_value=:discount_value,min_order_amount=:min_order_amount,max_uses=:max_uses,starts_at=:starts_at,ends_at=:ends_at,is_active=:is_active,updated_at=:now WHERE id=:id',

                $params + ['id' => $id]

            );

            return $id;

        }

        $this->execute(

            'INSERT INTO shop_coupons (code,name,discount_type,discount_value,min_order_amount,max_uses,starts_at,ends_at,is_active,created_at,updated_at) VALUES (:code,:name,:discount_type,:discount_value,:min_order_amount,:max_uses,:starts_at,:ends_at,:is_active,:now,:now)',

            $params

        );

        return (int) $this->lastInsertId();

    }



    public function deleteCoupon(int $id): void

    {

        $this->execute('DELETE FROM shop_coupons WHERE id = :id', ['id' => $id]);

    }



    /** @return array<int, array<string, mixed>> */

    public function allBanners(): array

    {

        return $this->fetchAll('SELECT * FROM shop_banners ORDER BY sort_order ASC, id DESC');

    }



    public function saveBanner(array $data): int

    {

        $now = date('Y-m-d H:i:s');

        $id = (int) ($data['id'] ?? 0);

        $params = [

            'title' => $data['title'],

            'subtitle' => $data['subtitle'] ?? '',

            'image_url' => $data['image_url'] ?? '',

            'link_url' => $data['link_url'] ?? '',

            'sort_order' => (int) ($data['sort_order'] ?? 0),

            'is_active' => (int) !empty($data['is_active']),

            'now' => $now,

        ];

        if ($id > 0) {

            $this->execute(

                'UPDATE shop_banners SET title=:title,subtitle=:subtitle,image_url=:image_url,link_url=:link_url,sort_order=:sort_order,is_active=:is_active,updated_at=:now WHERE id=:id',

                $params + ['id' => $id]

            );

            return $id;

        }

        $this->execute(

            'INSERT INTO shop_banners (title,subtitle,image_url,link_url,sort_order,is_active,created_at,updated_at) VALUES (:title,:subtitle,:image_url,:link_url,:sort_order,:is_active,:now,:now)',

            $params

        );

        return (int) $this->lastInsertId();

    }



    public function deleteBanner(int $id): void

    {

        $this->execute('DELETE FROM shop_banners WHERE id = :id', ['id' => $id]);

    }

    /** @return array<int, array<string, mixed>> */
    public function ordersByUser(int $userId, int $limit = 5): array
    {
        $limit = max(1, min(20, $limit));
        return $this->fetchAll(
            "SELECT * FROM shop_orders WHERE user_id = :user_id ORDER BY id DESC LIMIT {$limit}",
            ['user_id' => $userId]
        );
    }

    public function countOrdersByUser(int $userId, string $status = ''): int
    {
        if ($status === '') {
            $row = $this->fetchOne(
                'SELECT COUNT(*) AS cnt FROM shop_orders WHERE user_id = :user_id',
                ['user_id' => $userId]
            );
        } else {
            $row = $this->fetchOne(
                'SELECT COUNT(*) AS cnt FROM shop_orders WHERE user_id = :user_id AND status = :status',
                ['user_id' => $userId, 'status' => $status]
            );
        }
        return (int) ($row['cnt'] ?? 0);
    }

    /** @return array<int, array<string, mixed>> */
    public function activeCategories(): array
    {
        return $this->fetchAll(
            'SELECT * FROM shop_categories WHERE is_active = 1 ORDER BY sort_order ASC, id ASC'
        );
    }

    /** @return array<int, array<string, mixed>> */
    public function activeBanners(): array
    {
        return $this->fetchAll(
            'SELECT * FROM shop_banners WHERE is_active = 1 ORDER BY sort_order ASC, id ASC'
        );
    }

    /** @return array<int, array<string, mixed>> */
    public function activeSpecs(int $limit = 8): array
    {
        $limit = max(1, min(24, $limit));
        return $this->fetchAll(
            "SELECT * FROM label_specs WHERE is_active = 1 ORDER BY labels_per_sheet DESC, id ASC LIMIT {$limit}"
        );
    }

    public function findActiveProduct(int $id): ?array
    {
        return $this->fetchOne(
            'SELECT p.*, c.name AS category_name, c.slug AS category_slug,
                    s.name AS spec_name, s.width_mm, s.height_mm, s.material, s.shape, s.labels_per_sheet
             FROM shop_products p
             INNER JOIN shop_categories c ON c.id = p.category_id AND c.is_active = 1
             LEFT JOIN label_specs s ON s.id = p.spec_id
             WHERE p.id = :id AND p.status IN (\'active\', \'soldout\')
             LIMIT 1',
            ['id' => $id]
        );
    }

    /** @return array{items: array<int, array<string, mixed>>, total: int, page: int, pages: int} */
    public function activeProducts(array $filters = [], int $page = 1, int $perPage = 12): array
    {
        $page = max(1, $page);
        $total = $this->countActiveProducts($filters);
        $pages = max(1, (int) ceil($total / $perPage));
        $offset = ($page - 1) * $perPage;

        [$where, $params] = $this->productFilterClause($filters);
        $items = $this->fetchAll(
            "SELECT p.*, c.name AS category_name, c.slug AS category_slug,
                    s.name AS spec_name, s.width_mm, s.height_mm, s.material, s.shape, s.labels_per_sheet
             FROM shop_products p
             INNER JOIN shop_categories c ON c.id = p.category_id AND c.is_active = 1
             LEFT JOIN label_specs s ON s.id = p.spec_id
             WHERE p.status IN ('active','soldout') AND {$where}
             ORDER BY p.sort_order ASC, p.id DESC
             LIMIT {$perPage} OFFSET {$offset}",
            $params
        );

        return ['items' => $items, 'total' => $total, 'page' => $page, 'pages' => $pages, 'per_page' => $perPage];
    }

    public function countActiveProducts(array $filters = []): int
    {
        [$where, $params] = $this->productFilterClause($filters);
        $row = $this->fetchOne(
            "SELECT COUNT(*) AS cnt
             FROM shop_products p
             INNER JOIN shop_categories c ON c.id = p.category_id AND c.is_active = 1
             LEFT JOIN label_specs s ON s.id = p.spec_id
             WHERE p.status IN ('active','soldout') AND {$where}",
            $params
        );
        return (int) ($row['cnt'] ?? 0);
    }

    /** @return array<int, array<string, mixed>> */
    public function productsGroupedByMaterial(int $perGroup = 4): array
    {
        $perGroup = max(1, min(8, $perGroup));
        return $this->fetchAll(
            "SELECT p.*, c.name AS category_name, s.material, s.width_mm, s.height_mm, s.labels_per_sheet
             FROM shop_products p
             INNER JOIN shop_categories c ON c.id = p.category_id AND c.is_active = 1
             LEFT JOIN label_specs s ON s.id = p.spec_id
             WHERE p.status = 'active' AND s.material IS NOT NULL AND s.material != ''
             ORDER BY s.material ASC, p.sort_order ASC, p.id DESC
             LIMIT " . ($perGroup * 6)
        );
    }

    /** @return array<int, array<string, mixed>> */
    public function productImages(int $productId): array
    {
        return $this->fetchAll(
            'SELECT * FROM shop_product_images WHERE product_id = :pid ORDER BY sort_order ASC, id ASC',
            ['pid' => $productId]
        );
    }

    /** @param array<int, array{image_path:string,sort_order?:int,is_primary?:bool|int}> $images */
    public function syncProductImages(int $productId, array $images, ?string $thumbnail = null): void
    {
        $this->execute('DELETE FROM shop_product_images WHERE product_id = :pid', ['pid' => $productId]);
        $now = date('Y-m-d H:i:s');

        $normalized = [];
        foreach (array_values($images) as $i => $img) {
            $path = ShopProductImageService::normalizePublicPath((string) ($img['image_path'] ?? ''));
            if ($path === '') {
                continue;
            }
            $normalized[] = [
                'path' => $path,
                'sort_order' => (int) ($img['sort_order'] ?? $i),
                'is_primary' => !empty($img['is_primary']),
            ];
        }

        if ($normalized === []) {
            $this->execute(
                'UPDATE shop_products SET thumbnail = NULL, updated_at = :updated_at WHERE id = :id',
                ['updated_at' => $now, 'id' => $productId]
            );
            return;
        }

        $primaryIdx = 0;
        $thumbNorm = $thumbnail ? ShopProductImageService::normalizePublicPath($thumbnail) : '';
        if ($thumbNorm !== '') {
            foreach ($normalized as $i => $item) {
                if ($item['path'] === $thumbNorm) {
                    $primaryIdx = $i;
                    break;
                }
            }
        } else {
            foreach ($normalized as $i => $item) {
                if ($item['is_primary']) {
                    $primaryIdx = $i;
                    break;
                }
            }
        }

        $primaryPath = $normalized[$primaryIdx]['path'];
        foreach ($normalized as $i => $item) {
            $isPrimary = ($i === $primaryIdx);
            $this->execute(
                'INSERT INTO shop_product_images (product_id, image_path, sort_order, is_primary, created_at, updated_at)
                 VALUES (:product_id, :image_path, :sort_order, :is_primary, :created_at, :updated_at)',
                [
                    'product_id' => $productId,
                    'image_path' => $item['path'],
                    'sort_order' => $item['sort_order'],
                    'is_primary' => $isPrimary ? 1 : 0,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]
            );
        }

        $this->execute(
            'UPDATE shop_products SET thumbnail = :thumb, updated_at = :updated_at WHERE id = :id',
            ['thumb' => $primaryPath, 'updated_at' => $now, 'id' => $productId]
        );
    }

    /** @return array<int, array<int, array<string, mixed>>> */
    public function allProductImagesGrouped(): array
    {
        $rows = $this->fetchAll('SELECT * FROM shop_product_images ORDER BY product_id ASC, sort_order ASC, id ASC');
        $grouped = [];
        foreach ($rows as $row) {
            $grouped[(int) $row['product_id']][] = $row;
        }
        return $grouped;
    }

    private function encodeMetaJson(mixed $meta): ?string
    {
        if ($meta === null || $meta === '') {
            return null;
        }
        if (is_string($meta)) {
            return $meta;
        }
        if (!is_array($meta)) {
            return null;
        }
        $clean = array_filter($meta, static fn ($v) => $v !== null && $v !== '');
        if ($clean === []) {
            return null;
        }

        return json_encode($clean, JSON_UNESCAPED_UNICODE);
    }

    /** @return array{0:string,1:array<string,mixed>} */
    private function productFilterClause(array $filters): array
    {
        $where = '1=1';
        $params = [];

        if (!empty($filters['category_id'])) {
            $where .= ' AND p.category_id = :category_id';
            $params['category_id'] = (int) $filters['category_id'];
        }
        if (!empty($filters['category_slug'])) {
            $where .= ' AND c.slug = :category_slug';
            $params['category_slug'] = (string) $filters['category_slug'];
        }
        if (!empty($filters['material'])) {
            $where .= ' AND s.material LIKE :material';
            $params['material'] = '%' . $filters['material'] . '%';
        }
        if (!empty($filters['q'])) {
            $where .= ' AND (p.name LIKE :q OR p.sku LIKE :q OR p.description LIKE :q OR s.name LIKE :q)';
            $params['q'] = '%' . $filters['q'] . '%';
        }
        if (!empty($filters['shape'])) {
            $where .= ' AND s.shape = :shape';
            $params['shape'] = (string) $filters['shape'];
        }

        return [$where, $params];
    }

}
