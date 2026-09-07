<?php

declare(strict_types=1);

namespace App\Services;

use App\Repositories\ShopRepository;
use RuntimeException;

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
     *   clipart: ?array{url:string, prompt:string, title:string},
     *   template: ?array<string, mixed>,
     *   choices: ?array<int, array{id:string, title:string, desc:string}>,
     *   usage: ?array<string, mixed>,
     *   clipart_id: ?int
     * }
     */
    public function handle(array $messages, ?int $userId = null, string $surface = 'unknown', string $forceIntent = ''): array
    {
        $catalog = $this->buildCatalog();
        $hasImage = self::messagesHaveImage($messages);
        $explicit = $this->explicitImageMode($this->lastUserText($messages));
        $forced = $this->normalizeForceIntent($forceIntent);
        $officeParser = new OfficeDocumentParser();
        $officeSheet = null;
        try {
            $officeSheet = $officeParser->extractFromMessages($messages);
        } catch (RuntimeException $e) {
            if ($forced === 'generate_data_template' || $officeParser->messagesHaveOfficePart($messages)) {
                throw $e;
            }
        }

        $hasOffice = $officeSheet !== null;
        $difficulty = AiCostService::assessDifficulty($messages, $forced, $hasImage, $hasOffice);
        $this->openai->setChatModel(AiCostService::modelForDifficulty($difficulty));

        if (
            $officeSheet !== null
            && $forced !== 'generate_clipart'
            && $forced !== 'ask_image_mode'
        ) {
            $translateChoice = $this->resolveTranslateChoice($forced, $this->lastUserText($messages));
            if ($translateChoice === null && self::sheetHasForeignLanguage($officeSheet)) {
                return $this->replyAskTranslate(
                    self::sheetForeignSample($officeSheet),
                    'file',
                    $userId,
                    $surface,
                    $difficulty
                );
            }
            $translated = false;
            if ($translateChoice === 'translate_yes') {
                try {
                    $officeSheet = $this->openai->translateLabelSheet($officeSheet);
                    $translated = true;
                } catch (RuntimeException) {
                    $translated = false;
                }
            }
            return $this->handleOfficeTemplate($messages, $officeSheet, $userId, $surface, $difficulty, $translated);
        }

        $skipAssist = $forced === 'ask_image_mode'
            || $forced === 'translate_yes'
            || $forced === 'translate_no'
            || ($hasImage && $forced === '' && $explicit === null);

        $structured = [
            'intent' => 'chat',
            'message' => '',
            'product_id' => null,
            'search_query' => '',
            'clipart_prompt' => '',
            'width_mm' => 0.0,
            'height_mm' => 0.0,
        ];

        if (!$skipAssist) {
            $structured = $this->openai->chatLabelAssist($messages, $catalog);
        }

        $intent = (string) ($structured['intent'] ?? 'chat');
        $translateChoice = $this->resolveTranslateChoice($forced, $this->lastUserText($messages));
        if ($forced === 'translate_yes' || $forced === 'translate_no') {
            $intent = 'generate_template';
        } elseif ($forced !== '') {
            $intent = $forced;
        } elseif ($explicit !== null) {
            $intent = $explicit;
        } elseif ($hasImage && in_array($intent, ['generate_clipart', 'generate_template', 'chat'], true)) {
            $intent = 'ask_image_mode';
        }

        $reply = trim((string) ($structured['message'] ?? ''));
        if ($reply === '') {
            $reply = '요청을 확인했어요. 조금 더 구체적으로 말씀해 주시면 바로 도와드릴게요.';
        }

        $product = null;
        $clipart = null;
        $template = null;
        $choices = null;
        $clipartId = null;

        if ($intent === 'ask_image_mode') {
            $reply = '첨부하신 이미지를 봤어요. 라벨에 넣을 클립아트를 그릴까요, 아니면 완성된 라벨 템플릿을 만들까요?';
            $choices = self::imageModeChoices();
        } elseif ($intent === 'recommend_product') {
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
            if ($clipart && $userId !== null && $userId > 0) {
                $saved = (new UserAiClipartService())->saveForUser($userId, $clipart);
                $clipartId = $saved > 0 ? $saved : null;
            }
            if (!str_contains($reply, '클립아트') && !str_contains($reply, '이미지')) {
                $reply .= "\n\n라벨에 넣을 클립아트를 그려 두었어요. 이미지를 눌러 확대해 볼 수 있어요.";
            }
        } elseif ($intent === 'generate_template') {
            if ($translateChoice === null && $hasImage) {
                try {
                    $inspect = $this->openai->inspectImageLanguage($messages);
                } catch (RuntimeException) {
                    $inspect = ['has_foreign' => false, 'sample' => ''];
                }
                if (!empty($inspect['has_foreign'])) {
                    return $this->replyAskTranslate(
                        (string) ($inspect['sample'] ?? ''),
                        'image',
                        $userId,
                        $surface,
                        $difficulty
                    );
                }
            }
            $prompt = trim((string) ($structured['clipart_prompt'] ?? ''));
            if ($prompt === '') {
                $prompt = $this->fallbackTemplatePrompt($messages);
            } else {
                $prompt .= ' Full-bleed print-ready label artwork filling the entire canvas edge to edge. No mockup, no table, no torn paper, no watermark, no extra background around the label.';
            }
            if ($translateChoice === 'translate_yes') {
                $prompt .= ' Translate every readable product/label sentence into natural Korean. Keep numbers, units, barcodes, and graphical logos. Do not leave English/Japanese/Chinese body copy.';
            }
            $image = $this->openai->generateClipart($prompt);
            $image['title'] = '라비가 만든 라벨 템플릿';
            if ($image && $userId !== null && $userId > 0) {
                $saved = (new UserAiClipartService())->saveForUser($userId, $image);
                $clipartId = $saved > 0 ? $saved : null;
            }
            $size = $this->resolveTemplateSize($structured, $catalog);
            $template = $this->presentTemplate($image, $size['width_mm'], $size['height_mm']);
            if ($translateChoice === 'translate_yes') {
                $reply = '이미지의 외국어를 한국어로 번역해 라벨 템플릿을 만들었어요. 바로편집으로 이어서 다듬어 보세요.';
            } elseif (!str_contains($reply, '템플릿') && !str_contains($reply, '디자인')) {
                $reply = '첨부하신 이미지를 참고해 라벨 템플릿을 만들었어요. 바로편집으로 이어서 다듬어 보세요.';
            }
        }

        $usage = $this->openai->lastUsage();
        $usageView = AiCostService::present($usage, $intent, $difficulty);
        (new AiUsageService())->log([
            'user_id' => $userId ?? 0,
            'surface' => $surface,
            'intent' => $intent,
            'model' => $usageView['model'] ?? ($usage['model'] ?? null),
            'prompt_tokens' => $usageView['prompt_tokens'] ?? ($usage['prompt_tokens'] ?? null),
            'completion_tokens' => $usageView['completion_tokens'] ?? ($usage['completion_tokens'] ?? null),
            'total_tokens' => $usageView['total_tokens'] ?? ($usage['total_tokens'] ?? null),
            'cost_usd' => $usageView['usd'] ?? null,
            'cost_krw' => $usageView['krw'] ?? null,
            'agent' => $usageView['agent'] ?? null,
            'difficulty' => $usageView['difficulty'] ?? $difficulty,
            'has_image' => self::messagesHaveImage($messages),
            'clipart_id' => $clipartId,
            'status' => 'ok',
        ]);

        return [
            'reply' => $reply,
            'intent' => $intent,
            'product' => $product,
            'clipart' => $clipart,
            'template' => $template,
            'choices' => $choices,
            'usage' => $usageView,
            'clipart_id' => $clipartId,
        ];
    }

    /** @return array<int, array{id:string, title:string, desc:string}> */
    public static function imageModeChoices(): array
    {
        return [
            [
                'id' => 'generate_clipart',
                'title' => '클립아트 그리기',
                'desc' => '라벨 위에 올릴 일러스트만 그려 드려요',
            ],
            [
                'id' => 'generate_template',
                'title' => '템플릿 만들기',
                'desc' => '완성된 라벨 디자인으로 편집기를 열어 드려요',
            ],
        ];
    }

    /**
     * @param array<int, array{role:string, content:mixed}> $messages
     * @param array{source_name:string, source_kind:string, columns:array<int,string>, rows:array<int,array<int,string>>, summary:string} $sheet
     * @return array<string, mixed>
     */
    private function handleOfficeTemplate(array $messages, array $sheet, ?int $userId, string $surface, string $difficulty = 'hard', bool $translated = false): array
    {
        $ai = null;
        try {
            $ai = $this->openai->suggestDataLabelLayout(
                $sheet['source_name'],
                $sheet['columns'],
                $sheet['rows'],
                $this->lastUserText($messages)
            );
        } catch (RuntimeException) {
            $ai = null;
        }

        $builder = new LabiDataTemplateService();
        $built = $builder->build($sheet, $this->lastUserText($messages), $ai);
        $template = $builder->present($built, $sheet, $userId);
        $reply = (string) $built['message'];
        if ($translated) {
            $reply = '외국어 문구를 한국어로 옮긴 뒤 라벨을 구성했어요. ' . $reply;
        }

        $usage = $this->openai->lastUsage();
        $usageView = AiCostService::present($usage, 'generate_data_template', $difficulty);
        (new AiUsageService())->log([
            'user_id' => $userId ?? 0,
            'surface' => $surface,
            'intent' => 'generate_data_template',
            'model' => $usageView['model'] ?? ($usage['model'] ?? null),
            'prompt_tokens' => $usageView['prompt_tokens'] ?? ($usage['prompt_tokens'] ?? null),
            'completion_tokens' => $usageView['completion_tokens'] ?? ($usage['completion_tokens'] ?? null),
            'total_tokens' => $usageView['total_tokens'] ?? ($usage['total_tokens'] ?? null),
            'cost_usd' => $usageView['usd'] ?? null,
            'cost_krw' => $usageView['krw'] ?? null,
            'agent' => $usageView['agent'] ?? null,
            'difficulty' => $usageView['difficulty'] ?? $difficulty,
            'has_image' => false,
            'clipart_id' => null,
            'status' => 'ok',
        ]);

        return [
            'reply' => $reply,
            'intent' => 'generate_data_template',
            'product' => null,
            'clipart' => null,
            'template' => $template,
            'choices' => null,
            'usage' => $usageView,
            'clipart_id' => null,
        ];
    }

    private function normalizeForceIntent(string $intent): string
    {
        $intent = trim($intent);
        return in_array($intent, [
            'generate_clipart',
            'generate_template',
            'generate_data_template',
            'ask_image_mode',
            'translate_yes',
            'translate_no',
        ], true)
            ? $intent
            : '';
    }

    private function resolveTranslateChoice(string $forced, string $text): ?string
    {
        if ($forced === 'translate_yes' || $forced === 'translate_no') {
            return $forced;
        }
        $text = trim($text);
        if ($text === '') {
            return null;
        }
        if (preg_match('/원문\s*그대로|번역\s*하지\s*마|번역하지\s*마|영어\s*그대로|외국어\s*그대로/u', $text)) {
            return 'translate_no';
        }
        if (preg_match('/번역해|번역해서|한글로\s*바꿔|한국어로\s*바꿔|한국어로\s*번역/u', $text)) {
            return 'translate_yes';
        }
        return null;
    }

    /**
     * @param array{columns?:array<int,string>, rows?:array<int,array<int,string>>} $sheet
     */
    public static function sheetHasForeignLanguage(array $sheet): bool
    {
        return self::textLooksForeign(self::sheetPlainText($sheet));
    }

    /**
     * @param array{columns?:array<int,string>, rows?:array<int,array<int,string>>} $sheet
     */
    public static function sheetForeignSample(array $sheet): string
    {
        $best = '';
        foreach (self::sheetPlainChunks($sheet) as $chunk) {
            if (!self::textLooksForeign($chunk)) {
                continue;
            }
            $one = preg_replace('/\s+/u', ' ', trim($chunk)) ?? '';
            if (mb_strlen($one) > mb_strlen($best)) {
                $best = $one;
            }
        }
        if ($best === '') {
            return '';
        }
        return mb_strlen($best) > 28 ? mb_substr($best, 0, 28) . '…' : $best;
    }

    /**
     * @param array{columns?:array<int,string>, rows?:array<int,array<int,string>>} $sheet
     */
    private static function sheetPlainText(array $sheet): string
    {
        return trim(implode("\n", self::sheetPlainChunks($sheet)));
    }

    /**
     * @param array{columns?:array<int,string>, rows?:array<int,array<int,string>>} $sheet
     * @return array<int, string>
     */
    private static function sheetPlainChunks(array $sheet): array
    {
        $chunks = [];
        foreach ($sheet['columns'] ?? [] as $col) {
            $col = trim((string) $col);
            if ($col !== '') {
                $chunks[] = $col;
            }
        }
        foreach (array_slice($sheet['rows'] ?? [], 0, 40) as $row) {
            if (!is_array($row)) {
                continue;
            }
            foreach ($row as $cell) {
                $cell = trim((string) $cell);
                if ($cell !== '') {
                    $chunks[] = $cell;
                }
            }
        }
        return $chunks;
    }

    public static function textLooksForeign(string $text): bool
    {
        $letters = preg_match_all('/\p{L}/u', $text);
        if ($letters < 6) {
            return false;
        }
        $hangul = preg_match_all('/\p{Hangul}/u', $text);
        $cjk = preg_match_all('/[\p{Hiragana}\p{Katakana}\p{Han}]/u', $text);
        $other = preg_match_all('/[\p{Cyrillic}\p{Arabic}\p{Thai}]/u', $text);
        $latin = preg_match_all('/\p{Latin}/u', $text);
        if (($cjk + $other) >= 4) {
            return true;
        }
        return $latin >= 10 && ($hangul / max(1, $letters)) < 0.35;
    }

    /**
     * @return array<string, mixed>
     */
    private function replyAskTranslate(string $sample, string $source, ?int $userId, string $surface, string $difficulty): array
    {
        $from = $source === 'file' ? '첨부하신 파일' : '첨부하신 이미지';
        $hint = $sample !== '' ? "「{$sample}」 같은 문구가 보여요. " : '';
        $reply = $from . '에 한글이 아닌 외국어가 있어요. ' . $hint . '한국어로 번역해서 라벨을 만들까요?';
        $usage = $this->openai->lastUsage();
        $usageView = AiCostService::present($usage, 'ask_translate', $difficulty);
        (new AiUsageService())->log([
            'user_id' => $userId ?? 0,
            'surface' => $surface,
            'intent' => 'ask_translate',
            'model' => $usageView['model'] ?? ($usage['model'] ?? null),
            'prompt_tokens' => $usageView['prompt_tokens'] ?? ($usage['prompt_tokens'] ?? null),
            'completion_tokens' => $usageView['completion_tokens'] ?? ($usage['completion_tokens'] ?? null),
            'total_tokens' => $usageView['total_tokens'] ?? ($usage['total_tokens'] ?? null),
            'cost_usd' => $usageView['usd'] ?? null,
            'cost_krw' => $usageView['krw'] ?? null,
            'agent' => $usageView['agent'] ?? null,
            'difficulty' => $usageView['difficulty'] ?? $difficulty,
            'has_image' => $source === 'image',
            'clipart_id' => null,
            'status' => 'ok',
        ]);

        return [
            'reply' => $reply,
            'intent' => 'ask_translate',
            'product' => null,
            'clipart' => null,
            'template' => null,
            'choices' => [
                [
                    'id' => 'translate_yes',
                    'title' => '네, 번역해 주세요',
                    'desc' => '외국어를 한국어로 바꿔 라벨을 만들어요',
                ],
                [
                    'id' => 'translate_no',
                    'title' => '원문 그대로',
                    'desc' => '보이는 글자를 그대로 두고 만들어요',
                ],
            ],
            'usage' => $usageView,
            'clipart_id' => null,
        ];
    }

    /** @param array<int, array{role:string, content:mixed}> $messages */
    public function explicitImageMode(string $text): ?string
    {
        $text = trim($text);
        if ($text === '') {
            return null;
        }
        if (preg_match('/템플릿|라벨\s*디자인|전체\s*라벨|라벨지\s*만들|디자인으로\s*만들/u', $text)) {
            return 'generate_template';
        }
        if (preg_match('/그려|그림|클립아트|일러스트|아이콘|로고|캐릭터|스케치|드로잉/u', $text)) {
            return 'generate_clipart';
        }
        return null;
    }

    /** @param array<int, array{role:string, content:mixed}> $messages */
    public static function messagesHaveImage(array $messages): bool
    {
        foreach ($messages as $item) {
            $content = $item['content'] ?? null;
            if (!is_array($content)) {
                continue;
            }
            foreach ($content as $part) {
                if (is_array($part) && ($part['type'] ?? '') === 'image_url') {
                    return true;
                }
            }
        }
        return false;
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
        $labels = isset($product['labels_per_sheet']) ? (int) $product['labels_per_sheet'] : 0;
        $shape = (string) ($product['shape'] ?? '');
        $material = (string) ($product['material'] ?? '');
        $specLine = trim(implode(' · ', array_filter([
            $material,
            $shape,
            ($w !== null && $h !== null) ? "{$w}×{$h}mm" : '',
            $labels > 0 ? ($labels . '칸') : '',
        ])));

        $editorQuery = [];
        $sku = trim((string) ($product['sku'] ?? ''));
        if ($sku !== '') {
            $editorQuery['sku'] = $sku;
        }
        if ($w !== null && $h !== null) {
            $editorQuery['w'] = rtrim(rtrim(sprintf('%.2f', (float) $w), '0'), '.');
            $editorQuery['h'] = rtrim(rtrim(sprintf('%.2f', (float) $h), '0'), '.');
        }
        if ($labels > 0) {
            $editorQuery['labels'] = $labels;
        }
        if ($shape !== '') {
            $editorQuery['shape'] = $shape;
        }
        if ($material !== '') {
            $editorQuery['material'] = $material;
        }
        $productName = (string) ($product['name'] ?? '');
        if ($productName !== '') {
            $editorQuery['name'] = $productName;
        }
        $editorUrl = url('editor/');
        if ($editorQuery !== []) {
            $editorUrl .= '?' . http_build_query($editorQuery);
        }

        return [
            'id' => (int) $product['id'],
            'name' => $productName,
            'sku' => (string) ($product['sku'] ?? ''),
            'category' => (string) ($product['category_name'] ?? ''),
            'spec' => $specLine,
            'width_mm' => $w !== null ? (float) $w : null,
            'height_mm' => $h !== null ? (float) $h : null,
            'labels_per_sheet' => $labels > 0 ? $labels : null,
            'shape' => $shape,
            'material' => $material,
            'price' => $unit,
            'price_label' => $this->shopService->formatPrice($unit),
            'list_price' => (int) ($product['price'] ?? 0),
            'list_price_label' => $this->shopService->formatPrice((int) ($product['price'] ?? 0)),
            'on_sale' => !empty($product['sale_price']) && (int) $product['sale_price'] > 0
                && (int) $product['sale_price'] < (int) ($product['price'] ?? 0),
            'thumbnail' => $thumbUrl,
            'url' => url('shop/products/' . (int) $product['id']),
            'editor_url' => $editorUrl,
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

    /** @param array<int, array{role:string, content:mixed}> $messages */
    private function fallbackTemplatePrompt(array $messages): string
    {
        $hint = trim(mb_substr($this->lastUserText($messages) !== '' ? $this->lastUserText($messages) : 'product label', 0, 120));

        return "Print-ready full-bleed label sticker design filling the entire square canvas edge to edge. Professional packaging label inspired by: {$hint}. Clean layout, vivid print colors, no mockup, no wooden table, no torn paper, no watermark, no extra background around the label.";
    }

    /** @param array<int, array{role:string, content:mixed}> $messages */
    private function lastUserText(array $messages): string
    {
        for ($i = count($messages) - 1; $i >= 0; $i--) {
            if (($messages[$i]['role'] ?? '') !== 'user') {
                continue;
            }
            $content = $messages[$i]['content'] ?? '';
            if (is_string($content)) {
                return trim($content);
            }
            if (is_array($content)) {
                foreach ($content as $part) {
                    if (is_array($part) && ($part['type'] ?? '') === 'text') {
                        return trim((string) ($part['text'] ?? ''));
                    }
                }
            }
        }

        return '';
    }

    /**
     * @param array<string, mixed> $structured
     * @param array<int, array{id:int, name:string, sku:string, category:string, shape:string, size:string, material:string}> $catalog
     * @return array{width_mm:float, height_mm:float}
     */
    private function resolveTemplateSize(array $structured, array $catalog): array
    {
        $w = (float) ($structured['width_mm'] ?? 0);
        $h = (float) ($structured['height_mm'] ?? 0);
        if ($w >= 15 && $h >= 15) {
            $w = $this->clampMm($w, 20, 210);
            $h = $this->clampMm($h, 15, 297);
            if ($w <= 120 && $h <= 120) {
                return [
                    'width_mm' => $w,
                    'height_mm' => $h,
                ];
            }
        }

        $product = $this->resolveProduct($structured, $catalog);
        if ($product && ($product['width_mm'] ?? null) && ($product['height_mm'] ?? null)) {
            return [
                'width_mm' => (float) $product['width_mm'],
                'height_mm' => (float) $product['height_mm'],
            ];
        }

        return ['width_mm' => 70.0, 'height_mm' => 36.0];
    }

    private function clampMm(float $value, float $min, float $max): float
    {
        return max($min, min($max, $value));
    }

    /**
     * @param array{url:string, prompt:string, title:string} $image
     * @return array<string, mixed>
     */
    private function presentTemplate(array $image, float $widthMm, float $heightMm): array
    {
        $title = (string) ($image['title'] ?? '라비가 만든 라벨 템플릿');
        $url = (string) ($image['url'] ?? '');
        $query = [
            'w' => rtrim(rtrim(sprintf('%.2f', $widthMm), '0'), '.'),
            'h' => rtrim(rtrim(sprintf('%.2f', $heightMm), '0'), '.'),
            'name' => $title,
            'clipart' => $url,
            'fit' => 'cover',
        ];
        $editorUrl = url('editor/') . '?' . http_build_query($query);

        return [
            'url' => $url,
            'prompt' => (string) ($image['prompt'] ?? ''),
            'title' => $title,
            'width_mm' => $widthMm,
            'height_mm' => $heightMm,
            'fit' => 'cover',
            'editor_url' => $editorUrl,
        ];
    }
}
