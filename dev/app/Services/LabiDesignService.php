<?php

declare(strict_types=1);

namespace App\Services;

use App\Repositories\ShopRepository;

final class LabiDesignService
{
    private OpenAIService $openai;
    private ShopRepository $shop;
    private ShopService $shopService;

    public function __construct()
    {
        $this->openai = new OpenAIService();
        $this->shop = new ShopRepository();
        $this->shopService = new ShopService();
    }

    /**
     * @param array<int, array{role:string, content:mixed}> $messages
     * @return array{
     *   reply: string,
     *   intent: string,
     *   product: ?array<string, mixed>,
     *   clipart: ?array{url:string, prompt:string, title:string}
     * }
     */
    public function handle(array $messages): array
    {
        $catalog = $this->buildCatalog();
        $structured = $this->openai->chatLabelAssist($messages, $catalog);

        $intent = (string) ($structured['intent'] ?? 'chat');
        $reply = trim((string) ($structured['message'] ?? ''));
        if ($reply === '') {
            $reply = '요청을 확인했어요. 조금 더 구체적으로 말씀해 주시면 바로 도와드릴게요.';
        }

        $product = null;
        $clipart = null;

        if ($intent === 'recommend_product') {
            $product = $this->resolveProduct($structured, $catalog);
            if ($product === null) {
                $intent = 'chat';
                $reply .= "\n\n지금은 딱 맞는 등록 상품을 찾지 못했어요. 용도·모양·크기를 조금 더 알려주시면 다시 찾아볼게요.";
            } else {
                if (!str_contains($reply, $product['name'])) {
                    $reply .= "\n\n등록된 라벨 상품 중에서 「{$product['name']}」을(를) 추천드려요. 아래에서 미리보기로 확인해 보세요.";
                }
            }
        } elseif ($intent === 'generate_clipart') {
            $prompt = trim((string) ($structured['clipart_prompt'] ?? ''));
            if ($prompt === '') {
                $prompt = $this->fallbackClipartPrompt($messages);
            }
            $clipart = $this->openai->generateClipart($prompt);
            if (!str_contains($reply, '클립아트') && !str_contains($reply, '이미지')) {
                $reply .= "\n\n라벨에 넣을 클립아트를 그려 두었어요. 이미지를 눌러 확대해 볼 수 있어요.";
            }
        }

        return [
            'reply' => $reply,
            'intent' => $intent,
            'product' => $product,
            'clipart' => $clipart,
        ];
    }

    /**
     * @return array<int, array{id:int, name:string, sku:string, category:string, shape:string, size:string, material:string}>
     */
    private function buildCatalog(): array
    {
        $result = $this->shop->activeProducts([], 1, 200);
        $items = [];
        foreach ($result['items'] as $row) {
            if (($row['status'] ?? '') !== 'active') {
                continue;
            }
            $w = $row['width_mm'] ?? null;
            $h = $row['height_mm'] ?? null;
            $size = ($w !== null && $h !== null) ? "{$w}×{$h}mm" : '';
            $items[] = [
                'id' => (int) $row['id'],
                'name' => (string) ($row['name'] ?? ''),
                'sku' => (string) ($row['sku'] ?? ''),
                'category' => (string) ($row['category_name'] ?? ''),
                'shape' => (string) ($row['shape'] ?? ''),
                'size' => $size,
                'material' => (string) ($row['material'] ?? ''),
            ];
        }

        return $items;
    }

    /**
     * @param array<string, mixed> $structured
     * @param array<int, array{id:int, name:string, sku:string, category:string, shape:string, size:string, material:string}> $catalog
     * @return ?array<string, mixed>
     */
    private function resolveProduct(array $structured, array $catalog): ?array
    {
        $productId = (int) ($structured['product_id'] ?? 0);
        if ($productId > 0) {
            $product = $this->shop->findActiveProduct($productId);
            if ($product && ($product['status'] ?? '') === 'active') {
                return $this->presentProduct($product);
            }
        }

        $query = trim((string) ($structured['search_query'] ?? ''));
        if ($query !== '') {
            $found = $this->shop->activeProducts(['q' => $query], 1, 5);
            foreach ($found['items'] as $row) {
                if (($row['status'] ?? '') === 'active') {
                    return $this->presentProduct($row);
                }
            }
        }

        if ($catalog !== []) {
            $pick = $catalog[array_rand($catalog)];
            $product = $this->shop->findActiveProduct((int) $pick['id']);
            if ($product) {
                return $this->presentProduct($product);
            }
        }

        return null;
    }

    /** @param array<string, mixed> $product
     *  @return array<string, mixed>
     */
    private function presentProduct(array $product): array
    {
        $unit = $this->shopService->unitPrice($product);
        $thumbPath = (string) ($product['thumbnail'] ?? '');
        $thumbUrl = $thumbPath !== ''
            ? ShopProductImageService::resolveUrl($thumbPath)
            : asset('hero-tall-1.webp');

        $w = $product['width_mm'] ?? null;
        $h = $product['height_mm'] ?? null;
        $specLine = trim(implode(' · ', array_filter([
            (string) ($product['material'] ?? ''),
            (string) ($product['shape'] ?? ''),
            ($w !== null && $h !== null) ? "{$w}×{$h}mm" : '',
            !empty($product['labels_per_sheet']) ? ((int) $product['labels_per_sheet'] . '칸') : '',
        ])));

        return [
            'id' => (int) $product['id'],
            'name' => (string) ($product['name'] ?? ''),
            'sku' => (string) ($product['sku'] ?? ''),
            'category' => (string) ($product['category_name'] ?? ''),
            'spec' => $specLine,
            'price' => $unit,
            'price_label' => $this->shopService->formatPrice($unit),
            'list_price' => (int) ($product['price'] ?? 0),
            'list_price_label' => $this->shopService->formatPrice((int) ($product['price'] ?? 0)),
            'on_sale' => !empty($product['sale_price']) && (int) $product['sale_price'] > 0
                && (int) $product['sale_price'] < (int) ($product['price'] ?? 0),
            'thumbnail' => $thumbUrl,
            'url' => url('shop/products/' . (int) $product['id']),
        ];
    }

    /** @param array<int, array{role:string, content:mixed}> $messages */
    private function fallbackClipartPrompt(array $messages): string
    {
        $lastUser = '';
        for ($i = count($messages) - 1; $i >= 0; $i--) {
            if (($messages[$i]['role'] ?? '') !== 'user') {
                continue;
            }
            $content = $messages[$i]['content'] ?? '';
            if (is_string($content)) {
                $lastUser = $content;
                break;
            }
            if (is_array($content)) {
                foreach ($content as $part) {
                    if (is_array($part) && ($part['type'] ?? '') === 'text') {
                        $lastUser = (string) ($part['text'] ?? '');
                        break 2;
                    }
                }
            }
        }

        $hint = trim(mb_substr($lastUser !== '' ? $lastUser : 'cute label decoration', 0, 120));

        return "Simple clean label clipart illustration for sticker printing, white background, centered motif inspired by: {$hint}. Flat vector style, high contrast, no text, no watermark.";
    }
}
