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

            'kind' => self::normalizePaperKind($data['kind'] ?? null, (string) ($data['name'] ?? ''), (string) ($data['description'] ?? '')),

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

                'UPDATE label_specs SET name=:name,kind=:kind,image_path=:image_path,width_mm=:width_mm,height_mm=:height_mm,material=:material,shape=:shape,labels_per_sheet=:labels_per_sheet,description=:description,is_active=:is_active,updated_at=:now WHERE id=:id',

                $params + ['id' => $id]

            );

            return $id;

        }

        $insertParams = [
            'name' => $params['name'],
            'kind' => $params['kind'],
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

            'INSERT INTO label_specs (name,kind,image_path,width_mm,height_mm,material,shape,labels_per_sheet,description,is_active,created_at,updated_at) VALUES (:name,:kind,:image_path,:width_mm,:height_mm,:material,:shape,:labels_per_sheet,:description,:is_active,:created_at,:updated_at)',

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

    /**
     * @param array<string, mixed> $filters
     * @return array{items: array<int, array<string, mixed>>, total: int, page: int, pages: int}
     */
    public function adminProducts(array $filters = [], int $page = 1, int $perPage = 20): array
    {
        $page = max(1, $page);
        $perPage = max(1, min(100, $perPage));
        [$where, $params] = $this->productFilterClause($filters);

        $countRow = $this->fetchOne(
            "SELECT COUNT(*) AS cnt
             FROM shop_products p
             LEFT JOIN shop_categories c ON c.id = p.category_id
             LEFT JOIN label_specs s ON s.id = p.spec_id
             WHERE {$where}",
            $params
        );
        $total = (int) ($countRow['cnt'] ?? 0);
        $pages = max(1, (int) ceil($total / $perPage));
        if ($page > $pages) {
            $page = $pages;
        }
        $offset = ($page - 1) * $perPage;

        $items = $this->fetchAll(
            "SELECT p.*, c.name AS category_name, s.name AS spec_name
             FROM shop_products p
             LEFT JOIN shop_categories c ON c.id = p.category_id
             LEFT JOIN label_specs s ON s.id = p.spec_id
             WHERE {$where}
             ORDER BY p.sort_order ASC, p.id DESC
             LIMIT {$perPage} OFFSET {$offset}",
            $params
        );

        return ['items' => $items, 'total' => $total, 'page' => $page, 'pages' => $pages];
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

            'compat_formtec' => $this->nullableText($data['compat_formtec'] ?? null),

            'compat_ilabel' => $this->nullableText($data['compat_ilabel'] ?? null),

            'compat_anylabel' => $this->nullableText($data['compat_anylabel'] ?? null),

            'now' => $now,

        ];

        if ($id > 0) {

            $this->execute(

                'UPDATE shop_products SET category_id=:category_id,spec_id=:spec_id,name=:name,sku=:sku,price=:price,sale_price=:sale_price,stock_qty=:stock_qty,status=:status,description=:description,meta_json=:meta_json,compat_formtec=:compat_formtec,compat_ilabel=:compat_ilabel,compat_anylabel=:compat_anylabel,sort_order=:sort_order,thumbnail=:thumbnail,updated_at=:now WHERE id=:id',

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
            'compat_formtec' => $params['compat_formtec'],
            'compat_ilabel' => $params['compat_ilabel'],
            'compat_anylabel' => $params['compat_anylabel'],
            'created_at' => $now,
            'updated_at' => $now,
        ];

        $this->execute(

            'INSERT INTO shop_products (category_id,spec_id,name,sku,price,sale_price,stock_qty,status,description,meta_json,compat_formtec,compat_ilabel,compat_anylabel,sort_order,thumbnail,created_at,updated_at) VALUES (:category_id,:spec_id,:name,:sku,:price,:sale_price,:stock_qty,:status,:description,:meta_json,:compat_formtec,:compat_ilabel,:compat_anylabel,:sort_order,:thumbnail,:created_at,:updated_at)',

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

    public function findActiveProductByCode(string $code): ?array
    {
        $code = trim($code);
        if ($code === '') {
            return null;
        }

        if (preg_match('/^P(\d+)$/i', $code, $m) === 1) {
            $byId = $this->findActiveProduct((int) $m[1]);
            if ($byId) {
                return $byId;
            }
        }

        $select = 'SELECT p.*, c.name AS category_name, c.slug AS category_slug,
                          s.name AS spec_name, s.width_mm, s.height_mm, s.material, s.shape, s.labels_per_sheet
                   FROM shop_products p
                   INNER JOIN shop_categories c ON c.id = p.category_id AND c.is_active = 1
                   LEFT JOIN label_specs s ON s.id = p.spec_id
                   WHERE p.status IN (\'active\', \'soldout\')';

        $exact = $this->fetchOne(
            $select . ' AND (p.sku = :sku OR p.compat_formtec = :cf OR p.compat_ilabel = :ci OR p.compat_anylabel = :ca)
             ORDER BY CASE WHEN p.sku = :sku_rank THEN 0 ELSE 1 END, p.id DESC
             LIMIT 1',
            [
                'sku' => $code,
                'cf' => $code,
                'ci' => $code,
                'ca' => $code,
                'sku_rank' => $code,
            ]
        );
        if ($exact) {
            return $exact;
        }

        $like = '%' . $code . '%';
        return $this->fetchOne(
            $select . ' AND (p.compat_formtec LIKE :lf OR p.compat_ilabel LIKE :li OR p.compat_anylabel LIKE :la)
             ORDER BY p.id DESC
             LIMIT 1',
            [
                'lf' => $like,
                'li' => $like,
                'la' => $like,
            ]
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

    /**
     * 편집기 용지 선택용 — 판매중/품절 상품 + 규격 + 호환코드.
     *
     * @return array{items: array<int, array<string, mixed>>, categories: array<int, array{id:int,name:string}>}
     */
    public function editorPapers(): array
    {
        $rows = $this->fetchAll(
            "SELECT p.id, p.name, p.sku, p.thumbnail, p.category_id, p.spec_id, p.status,
                    p.compat_formtec, p.compat_ilabel, p.compat_anylabel,
                    c.name AS category_name,
                    s.name AS spec_name, s.kind AS spec_kind, s.width_mm, s.height_mm, s.material, s.shape, s.labels_per_sheet
             FROM shop_products p
             LEFT JOIN shop_categories c ON c.id = p.category_id
             LEFT JOIN label_specs s ON s.id = p.spec_id
             WHERE p.status IN ('active','soldout')
             ORDER BY p.sort_order ASC, p.name ASC, p.id DESC
             LIMIT 500"
        );

        $items = [];
        $categories = [];
        foreach ($rows as $row) {
            $categoryId = (int) ($row['category_id'] ?? 0);
            $categoryName = trim((string) ($row['category_name'] ?? ''));
            if ($categoryId > 0 && $categoryName !== '' && !isset($categories[$categoryId])) {
                $categories[$categoryId] = ['id' => $categoryId, 'name' => $categoryName];
            }
            $items[] = [
                'id' => (int) ($row['id'] ?? 0),
                'name' => (string) ($row['name'] ?? ''),
                'sku' => (string) ($row['sku'] ?? ''),
                'kind' => self::normalizePaperKind(
                    $row['spec_kind'] ?? null,
                    $categoryName . ' ' . (string) ($row['name'] ?? '') . ' ' . (string) ($row['sku'] ?? '') . ' ' . (string) ($row['spec_name'] ?? '')
                ),
                'thumbnailUrl' => ShopProductImageService::resolveUrl((string) ($row['thumbnail'] ?? '')),
                'categoryId' => $categoryId,
                'categoryName' => $categoryName,
                'specId' => !empty($row['spec_id']) ? (int) $row['spec_id'] : null,
                'specName' => (string) ($row['spec_name'] ?? ''),
                'widthMm' => (float) ($row['width_mm'] ?? 0),
                'heightMm' => (float) ($row['height_mm'] ?? 0),
                'shape' => (string) ($row['shape'] ?? ''),
                'labelsPerSheet' => (int) ($row['labels_per_sheet'] ?? 0),
                'material' => (string) ($row['material'] ?? ''),
                'compatFormtec' => (string) ($row['compat_formtec'] ?? ''),
                'compatIlabel' => (string) ($row['compat_ilabel'] ?? ''),
                'compatAnylabel' => (string) ($row['compat_anylabel'] ?? ''),
            ];
        }

        return [
            'items' => $items,
            'categories' => array_values($categories),
        ];
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
            $where .= ' AND (p.name LIKE :q_name OR p.sku LIKE :q_sku OR p.description LIKE :q_desc OR s.name LIKE :q_spec'
                . ' OR p.compat_formtec LIKE :q_formtec OR p.compat_ilabel LIKE :q_ilabel OR p.compat_anylabel LIKE :q_anylabel)';
            $like = '%' . $filters['q'] . '%';
            $params['q_name'] = $like;
            $params['q_sku'] = $like;
            $params['q_desc'] = $like;
            $params['q_spec'] = $like;
            $params['q_formtec'] = $like;
            $params['q_ilabel'] = $like;
            $params['q_anylabel'] = $like;
        }
        if (!empty($filters['shape'])) {
            $where .= ' AND s.shape = :shape';
            $params['shape'] = (string) $filters['shape'];
        }
        if (!empty($filters['status'])) {
            $where .= ' AND p.status = :status';
            $params['status'] = (string) $filters['status'];
        }
        if (!empty($filters['spec_id'])) {
            $where .= ' AND p.spec_id = :spec_id';
            $params['spec_id'] = (int) $filters['spec_id'];
        }
        if (!empty($filters['compat_missing'])) {
            $where .= " AND IFNULL(p.compat_formtec,'') = '' AND IFNULL(p.compat_ilabel,'') = '' AND IFNULL(p.compat_anylabel,'') = ''";
        }

        return [$where, $params];
    }

    public function updateProductCompat(int $id, array $codes): void
    {
        $this->execute(
            'UPDATE shop_products
             SET compat_formtec = :compat_formtec, compat_ilabel = :compat_ilabel, compat_anylabel = :compat_anylabel, updated_at = :now
             WHERE id = :id',
            [
                'compat_formtec' => $this->nullableText($codes['compat_formtec'] ?? null),
                'compat_ilabel' => $this->nullableText($codes['compat_ilabel'] ?? null),
                'compat_anylabel' => $this->nullableText($codes['compat_anylabel'] ?? null),
                'now' => date('Y-m-d H:i:s'),
                'id' => $id,
            ]
        );
    }

    /**
     * @param array<string, mixed> $filters
     * @return array{items: array<int, array<string, mixed>>, total: int, page: int, pages: int, per_page: int}
     */
    public function paginateAdminOrders(array $filters = [], int $page = 1, int $perPage = 20): array
    {
        $page = max(1, $page);
        $perPage = max(1, min(5000, $perPage));
        [$where, $params] = $this->orderFilterClause($filters);

        $countRow = $this->fetchOne(
            "SELECT COUNT(*) AS cnt FROM shop_orders o WHERE {$where}",
            $params
        );
        $total = (int) ($countRow['cnt'] ?? 0);
        $pages = max(1, (int) ceil($total / $perPage));
        if ($page > $pages) {
            $page = $pages;
        }
        $offset = ($page - 1) * $perPage;

        $items = $this->fetchAll(
            "SELECT o.* FROM shop_orders o WHERE {$where} ORDER BY o.id DESC LIMIT {$perPage} OFFSET {$offset}",
            $params
        );
        $this->attachOrderItems($items);

        return ['items' => $items, 'total' => $total, 'page' => $page, 'pages' => $pages, 'per_page' => $perPage];
    }

    /** @return array<string, int> */
    public function orderStatusCounts(array $filters = []): array
    {
        $countFilters = $filters;
        unset($countFilters['status']);
        [$where, $params] = $this->orderFilterClause($countFilters);
        $rows = $this->fetchAll(
            "SELECT o.status, COUNT(*) AS cnt FROM shop_orders o WHERE {$where} GROUP BY o.status",
            $params
        );
        $counts = [
            'all' => 0,
            'pending' => 0,
            'paid' => 0,
            'preparing' => 0,
            'shipping' => 0,
            'delivered' => 0,
            'cancelled' => 0,
            'refunded' => 0,
        ];
        foreach ($rows as $row) {
            $key = (string) ($row['status'] ?? '');
            $n = (int) ($row['cnt'] ?? 0);
            if (isset($counts[$key])) {
                $counts[$key] = $n;
            }
            $counts['all'] += $n;
        }
        return $counts;
    }

    public function findAdminOrder(int $id): ?array
    {
        $row = $this->fetchOne('SELECT * FROM shop_orders WHERE id = :id LIMIT 1', ['id' => $id]);
        if (!$row) {
            return null;
        }
        $items = [$row];
        $this->attachOrderItems($items);
        return $items[0];
    }

    /**
     * @param array<int, array<string, mixed>> $orders
     */
    private function attachOrderItems(array &$orders): void
    {
        if ($orders === []) {
            return;
        }
        $ids = [];
        foreach ($orders as $row) {
            $ids[] = (int) ($row['id'] ?? 0);
        }
        $ids = array_values(array_filter($ids));
        if ($ids === []) {
            return;
        }
        $placeholders = [];
        $params = [];
        foreach ($ids as $i => $id) {
            $key = 'oid' . $i;
            $placeholders[] = ':' . $key;
            $params[$key] = $id;
        }
        $rows = $this->fetchAll(
            'SELECT * FROM shop_order_items WHERE order_id IN (' . implode(',', $placeholders) . ') ORDER BY id ASC',
            $params
        );
        $grouped = [];
        foreach ($rows as $item) {
            $grouped[(int) $item['order_id']][] = $item;
        }
        foreach ($orders as &$order) {
            $oid = (int) ($order['id'] ?? 0);
            $order['items'] = $grouped[$oid] ?? [];
            $order['item_count'] = count($order['items']);
            $order['item_qty'] = 0;
            foreach ($order['items'] as $item) {
                $order['item_qty'] += (int) ($item['qty'] ?? 0);
            }
        }
        unset($order);
    }

    /** @return array{0:string,1:array<string,mixed>} */
    private function orderFilterClause(array $filters): array
    {
        $where = '1=1';
        $params = [];

        $status = trim((string) ($filters['status'] ?? ''));
        if ($status === 'cancel_group') {
            $where .= " AND o.status IN ('cancelled','refunded')";
        } elseif ($status !== '') {
            $where .= ' AND o.status = :status';
            $params['status'] = $status;
        }

        $payment = trim((string) ($filters['payment_status'] ?? ''));
        if ($payment !== '') {
            $where .= ' AND o.payment_status = :payment_status';
            $params['payment_status'] = $payment;
        }

        if (!empty($filters['date_from'])) {
            $where .= ' AND o.created_at >= :date_from';
            $params['date_from'] = $filters['date_from'] . ' 00:00:00';
        }
        if (!empty($filters['date_to'])) {
            $where .= ' AND o.created_at <= :date_to';
            $params['date_to'] = $filters['date_to'] . ' 23:59:59';
        }

        if (!empty($filters['missing_tracking'])) {
            $where .= " AND o.status IN ('paid','preparing','shipping') AND IFNULL(o.tracking_no,'') = ''";
        }

        $q = trim((string) ($filters['q'] ?? ''));
        if ($q !== '') {
            $where .= ' AND (
                o.order_no LIKE :oq1 OR o.customer_name LIKE :oq2 OR o.customer_email LIKE :oq3
                OR o.customer_phone LIKE :oq4 OR o.shipping_name LIKE :oq5 OR o.shipping_phone LIKE :oq6
                OR o.tracking_no LIKE :oq7
                OR EXISTS (
                    SELECT 1 FROM shop_order_items i
                    WHERE i.order_id = o.id AND (i.product_name LIKE :oq8 OR i.sku LIKE :oq9)
                )
            )';
            $like = '%' . $q . '%';
            foreach (range(1, 9) as $n) {
                $params['oq' . $n] = $like;
            }
        }

        return [$where, $params];
    }

    private function nullableText(mixed $value): ?string
    {
        $text = trim((string) ($value ?? ''));
        return $text === '' ? null : $text;
    }

    /**
     * @param array<string, mixed> $order
     * @param array<int, array<string, mixed>> $items
     * @return array{id:int,order_no:string}
     */
    public function createCustomerOrder(array $order, array $items): array
    {
        $this->db->beginTransaction();
        try {
            $orderNo = $this->nextOrderNo();
            $now = date('Y-m-d H:i:s');
            $this->execute(
                'INSERT INTO shop_orders (
                    order_no, user_id, customer_name, customer_email, customer_phone,
                    status, payment_status, subtotal, shipping_fee, discount_amount, total_amount,
                    shipping_name, shipping_phone, shipping_address, shipping_memo,
                    created_at, updated_at
                ) VALUES (
                    :order_no, :user_id, :customer_name, :customer_email, :customer_phone,
                    :status, :payment_status, :subtotal, :shipping_fee, :discount_amount, :total_amount,
                    :shipping_name, :shipping_phone, :shipping_address, :shipping_memo,
                    :created_at, :updated_at
                )',
                [
                    'order_no' => $orderNo,
                    'user_id' => $order['user_id'] ?? null,
                    'customer_name' => $order['customer_name'],
                    'customer_email' => $order['customer_email'],
                    'customer_phone' => $order['customer_phone'] ?? null,
                    'status' => 'pending',
                    'payment_status' => 'pending',
                    'subtotal' => (int) ($order['subtotal'] ?? 0),
                    'shipping_fee' => (int) ($order['shipping_fee'] ?? 0),
                    'discount_amount' => (int) ($order['discount_amount'] ?? 0),
                    'total_amount' => (int) ($order['total_amount'] ?? 0),
                    'shipping_name' => $order['shipping_name'] ?? $order['customer_name'],
                    'shipping_phone' => $order['shipping_phone'] ?? ($order['customer_phone'] ?? null),
                    'shipping_address' => $order['shipping_address'] ?? null,
                    'shipping_memo' => $order['shipping_memo'] ?? null,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]
            );
            $orderId = (int) $this->lastInsertId();
            foreach ($items as $item) {
                $this->execute(
                    'INSERT INTO shop_order_items (order_id, product_id, product_name, sku, qty, unit_price, line_total)
                     VALUES (:order_id, :product_id, :product_name, :sku, :qty, :unit_price, :line_total)',
                    [
                        'order_id' => $orderId,
                        'product_id' => (int) ($item['id'] ?? 0) ?: null,
                        'product_name' => (string) ($item['name'] ?? ''),
                        'sku' => (string) ($item['sku'] ?? ''),
                        'qty' => (int) ($item['qty'] ?? 1),
                        'unit_price' => (int) ($item['unit_price'] ?? 0),
                        'line_total' => (int) ($item['line_total'] ?? 0),
                    ]
                );
                $pid = (int) ($item['id'] ?? 0);
                $qty = (int) ($item['qty'] ?? 1);
                if ($pid > 0 && $qty > 0) {
                    $fresh = $this->findActiveProduct($pid);
                    $left = max(0, (int) ($fresh['stock_qty'] ?? 0) - $qty);
                    $this->execute(
                        'UPDATE shop_products SET stock_qty = :qty, status = :status, updated_at = :now WHERE id = :id',
                        [
                            'qty' => $left,
                            'status' => $left <= 0 ? 'soldout' : (string) ($fresh['status'] ?? 'active'),
                            'now' => $now,
                            'id' => $pid,
                        ]
                    );
                }
            }
            $this->db->commit();
            return ['id' => $orderId, 'order_no' => $orderNo];
        } catch (\Throwable $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    private function nextOrderNo(): string
    {
        $prefix = 'LU' . date('Ymd');
        $row = $this->fetchOne(
            'SELECT order_no FROM shop_orders WHERE order_no LIKE :prefix ORDER BY order_no DESC LIMIT 1',
            ['prefix' => $prefix . '%']
        );
        $seq = 1;
        if ($row && preg_match('/(\d{4})$/', (string) ($row['order_no'] ?? ''), $m)) {
            $seq = ((int) $m[1]) + 1;
        }
        return $prefix . str_pad((string) $seq, 4, '0', STR_PAD_LEFT);
    }

    public static function normalizePaperKind(mixed $kind, string $hint = '', string $extra = ''): string
    {
        $raw = strtolower(trim((string) $kind));
        if ($raw === 'tag') {
            return 'tag';
        }
        if ($raw === 'label') {
            return 'label';
        }
        $hay = mb_strtolower($hint . ' ' . $extra);
        if (
            str_contains($hay, '태그')
            || str_contains($hay, '행택')
            || preg_match('/(?<![a-z])tag(?![a-z])/u', $hay)
            || str_contains($hay, 'hangtag')
        ) {
            return 'tag';
        }
        return 'label';
    }

}
