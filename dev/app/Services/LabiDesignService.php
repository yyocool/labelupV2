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
                    $inspect = ['has_foreign' => false, 'sample' => '', 'lang' => 'ko'];
                }
                if (self::shouldAskImageTranslate($inspect)) {
                    return $this->replyAskTranslate(
                        (string) ($inspect['sample'] ?? ''),
                        'image',
                        $userId,
                        $surface,
                        $difficulty
                    );
                }
            }
            $size = $this->resolveTemplateSize($structured, $catalog);
            $layout = null;
            $translateToKo = $translateChoice === 'translate_yes';

            // HARD RULE: 템플릿의 변경 가능 문구는 반드시 텍스트 오브젝트.
            // 이미지에는 글자·숫자·특수문자를 넣지 않는다.
            if ($hasImage) {
                try {
                    $layout = $this->openai->extractLabelLayout($messages, $translateToKo);
                } catch (RuntimeException) {
                    $layout = null;
                }
            }
            if (!is_array($layout) || ($layout['texts'] ?? []) === []) {
                try {
                    $layout = $this->openai->planEditableLabelTemplate(
                        $messages,
                        $translateToKo,
                        trim((string) ($structured['clipart_prompt'] ?? '')) !== ''
                            ? (string) $structured['message'] . ' ' . $this->lastUserText($messages)
                            : $this->lastUserText($messages)
                    );
                } catch (RuntimeException) {
                    $layout = null;
                }
            }
            if (!is_array($layout) || ($layout['texts'] ?? []) === []) {
                $layout = [
                    'title' => '라비가 만든 라벨 템플릿',
                    'width_mm' => $size['width_mm'],
                    'height_mm' => $size['height_mm'],
                    'background_prompt' => '',
                    'texts' => [[
                        'text' => trim(mb_substr($this->lastUserText($messages) !== '' ? $this->lastUserText($messages) : '상품명', 0, 24)),
                        'x' => 0.08,
                        'y' => 0.28,
                        'w' => 0.84,
                        'h' => 0.22,
                        'font_size_mm' => 5.5,
                        'bold' => true,
                        'align' => 'center',
                        'color' => '#7B2840',
                    ]],
                ];
            }

            $lw = (float) ($layout['width_mm'] ?? 0);
            $lh = (float) ($layout['height_mm'] ?? 0);
            if ($lw >= 15 && $lh >= 15) {
                $size = [
                    'width_mm' => $this->clampMm($lw, 20, 210),
                    'height_mm' => $this->clampMm($lh, 15, 297),
                ];
            }

            $texts = is_array($layout['texts'] ?? null) ? $layout['texts'] : [];
            $bgHint = trim((string) ($layout['background_prompt'] ?? ''));
            if ($bgHint === '') {
                $bgHint = trim((string) ($structured['clipart_prompt'] ?? ''));
            }
            if ($bgHint === '') {
                $bgHint = $this->fallbackTemplatePrompt($messages);
            }
            $prompt = $bgHint
                . ' Full-bleed print-ready label BACKGROUND only, filling the entire canvas edge to edge.'
                . ' Absolutely NO letters, NO numbers, NO digits, NO punctuation, NO words, NO watermarks, NO barcodes as text.'
                . ' Keep colors, shapes, ornaments, patterns, borders, and blank areas where text belonged.'
                . ' No mockup, no table, no torn paper, no extra background around the label.';

            $image = $this->openai->generateClipart($prompt);
            $title = trim((string) ($layout['title'] ?? ''));
            if ($title === '') {
                $title = '라비가 만든 라벨 템플릿';
            }
            $image['title'] = $title;
            if ($image && $userId !== null && $userId > 0) {
                $saved = (new UserAiClipartService())->saveForUser($userId, $image);
                $clipartId = $saved > 0 ? $saved : null;
            }
            $template = $this->presentTemplate(
                $image,
                $size['width_mm'],
                $size['height_mm'],
                $texts
            );
            if ($translateChoice === 'translate_yes') {
                $reply = '이미지의 외국어를 한국어로 번역해, 글자는 편집 가능한 텍스트로 분리한 라벨 템플릿을 만들었어요. 바로편집에서 문구를 바꿔 보세요.';
            } else {
                $reply = '글자·숫자·특수문자는 편집 가능한 텍스트로, 배경만 이미지로 만든 라벨 템플릿이에요. 바로편집에서 문구를 바꿔 보세요.';
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
        $text = trim($text);
        if ($text === '') {
            return false;
        }
        $letters = preg_match_all('/\p{L}/u', $text);
        if ($letters < 8) {
            return false;
        }
        $hangul = preg_match_all('/\p{Hangul}/u', $text);
        $kana = preg_match_all('/[\p{Hiragana}\p{Katakana}]/u', $text);
        $han = preg_match_all('/\p{Han}/u', $text);
        $other = preg_match_all('/[\p{Cyrillic}\p{Arabic}\p{Thai}]/u', $text);
        $latin = preg_match_all('/\p{Latin}/u', $text);

        // 한글이 충분히 섞인 본문은 한국어로 본다 (영문 브랜드·단위가 있어도 번역 질문 생략)
        if ($hangul > 0 && ($hangul / max(1, $letters)) >= 0.35) {
            return false;
        }
        if (($kana + $other) >= 4) {
            return true;
        }
        // 한글 없이 한자가 많으면 중·일 계열로 본다
        if ($han >= 6 && $hangul === 0) {
            return true;
        }
        // 짧은 영문 로고/약어는 외국어 본문으로 보지 않음
        if ($latin > 0 && $latin <= 12 && !preg_match('/\s/u', $text) && $hangul === 0 && $kana === 0 && $han === 0) {
            return false;
        }

        return $latin >= 12 && ($hangul / max(1, $letters)) < 0.2;
    }

    /**
     * 이미지 언어 검사 결과로 번역 질문을 띄울지 최종 판단.
     * 한글(ko)이거나 의미 있는 외국어 본문이 아니면 false.
     *
     * @param array{has_foreign?:mixed, sample?:string, lang?:string} $inspect
     */
    public static function shouldAskImageTranslate(array $inspect): bool
    {
        $lang = strtolower(trim((string) ($inspect['lang'] ?? '')));
        if (in_array($lang, ['ko', 'kr', 'kor', 'korean'], true)) {
            return false;
        }
        if (empty($inspect['has_foreign'])) {
            return false;
        }
        $sample = trim((string) ($inspect['sample'] ?? ''));
        if ($sample !== '' && self::textLooksForeign($sample)) {
            return true;
        }
        // 샘플이 짧아도 일/중/기타로 명확히 표시된 경우만 질문
        if (in_array($lang, ['ja', 'jp', 'jpn', 'japanese', 'zh', 'cn', 'zho', 'chinese', 'other'], true)) {
            return $sample === '' || mb_strlen($sample) >= 2;
        }
        if (in_array($lang, ['en', 'eng', 'english'], true)) {
            // 영문은 문장성(공백 포함·충분한 길이)일 때만
            return $sample !== '' && (preg_match('/\s/u', $sample) || mb_strlen($sample) >= 16);
        }

        return false;
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

        return "Print-ready full-bleed label BACKGROUND only for sticker printing, filling the entire canvas edge to edge. Soft packaging-style colors inspired by: {$hint}. Decorations, shapes, borders, patterns only. Absolutely NO letters, NO numbers, NO digits, NO words, NO watermarks. No mockup, no wooden table, no torn paper, no extra background around the label.";
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
     * @param array<int, array{
     *   text:string,
     *   x:float,
     *   y:float,
     *   w:float,
     *   h:float,
     *   font_size_mm:float,
     *   bold:bool,
     *   align:string,
     *   color:string
     * }> $texts
     * @return array<string, mixed>
     */
    private function presentTemplate(array $image, float $widthMm, float $heightMm, array $texts = []): array
    {
        $title = (string) ($image['title'] ?? '라비가 만든 라벨 템플릿');
        $url = (string) ($image['url'] ?? '');
        $w = max(10.0, $widthMm);
        $h = max(10.0, $heightMm);

        if ($texts !== [] && $url !== '') {
            $document = $this->buildEditableTemplateDocument($url, $title, $w, $h, $texts);
            $editorUrl = url('editor/') . '?labiDoc=1';

            return [
                'url' => $url,
                'prompt' => (string) ($image['prompt'] ?? ''),
                'title' => $title,
                'width_mm' => $w,
                'height_mm' => $h,
                'fit' => 'cover',
                'editor_url' => $editorUrl,
                'document' => $document,
                'editable_texts' => count($texts),
            ];
        }

        $query = [
            'w' => rtrim(rtrim(sprintf('%.2f', $w), '0'), '.'),
            'h' => rtrim(rtrim(sprintf('%.2f', $h), '0'), '.'),
            'name' => $title,
            'clipart' => $url,
            'fit' => 'cover',
        ];
        $editorUrl = url('editor/') . '?' . http_build_query($query);

        return [
            'url' => $url,
            'prompt' => (string) ($image['prompt'] ?? ''),
            'title' => $title,
            'width_mm' => $w,
            'height_mm' => $h,
            'fit' => 'cover',
            'editor_url' => $editorUrl,
        ];
    }

    /**
     * @param array<int, array{
     *   text:string,
     *   x:float,
     *   y:float,
     *   w:float,
     *   h:float,
     *   font_size_mm:float,
     *   bold:bool,
     *   align:string,
     *   color:string
     * }> $texts
     * @return array<string, mixed>
     */
    private function buildEditableTemplateDocument(
        string $imageUrl,
        string $title,
        float $w,
        float $h,
        array $texts
    ): array {
        $paper = [
            'version' => 1,
            'paperNo' => 'LU-AI',
            'name' => sprintf('%s×%s mm', rtrim(rtrim(sprintf('%.1f', $w), '0'), '.'), rtrim(rtrim(sprintf('%.1f', $h), '0'), '.')),
            'category' => 'Custom',
            'brand' => 'LabelUp',
            'paperWidthMm' => $w,
            'paperHeightMm' => $h,
            'labelWidthMm' => $w,
            'labelHeightMm' => $h,
            'columns' => 1,
            'rows' => 1,
            'leftMarginMm' => 0,
            'topMarginMm' => 0,
            'rightMarginMm' => 0,
            'bottomMarginMm' => 0,
            'hGapMm' => 0,
            'vGapMm' => 0,
            'labelColor' => '#FFFFFF',
            'shape' => ['kind' => 'rect'],
        ];

        $objects = [
            [
                'id' => 'labiBg01',
                'type' => 'image',
                'zIndex' => 0,
                'visible' => true,
                'locked' => false,
                'x' => 0,
                'y' => 0,
                'width' => $w,
                'height' => $h,
                'fill' => 'transparent',
                'strokeWidth' => 0,
                'opacity' => 1,
                'imageData' => $imageUrl,
                'imageFit' => 'cover',
                'backgroundTransparent' => true,
            ],
        ];

        $z = 1;
        foreach ($texts as $i => $row) {
            $boxW = max(4.0, (float) $row['w'] * $w);
            $boxH = max(3.0, (float) $row['h'] * $h);
            $x = max(0.0, min($w - 2.0, (float) $row['x'] * $w));
            $y = max(0.0, min($h - 2.0, (float) $row['y'] * $h));
            if ($x + $boxW > $w) {
                $boxW = max(3.0, $w - $x);
            }
            if ($y + $boxH > $h) {
                $boxH = max(2.5, $h - $y);
            }
            $font = (float) ($row['font_size_mm'] ?? 0);
            if ($font < 1.5) {
                $font = max(1.8, min(12.0, $boxH * 0.72));
            }
            $font = max(1.5, min(14.0, $font));
            // 라벨 크기에 맞게 한 번 더 스케일 (정규화 추정값이 절대 mm로 올 때 보정)
            if ($font > $boxH * 1.15) {
                $font = max(1.5, $boxH * 0.78);
            }

            $objects[] = [
                'id' => sprintf('labiTx%02d', $i + 1),
                'type' => 'text',
                'zIndex' => $z++,
                'visible' => true,
                'locked' => false,
                'x' => round($x, 2),
                'y' => round($y, 2),
                'width' => round($boxW, 2),
                'height' => round($boxH, 2),
                'fill' => (string) ($row['color'] ?? '#2E2A27'),
                'strokeWidth' => 0,
                'opacity' => 1,
                'text' => (string) $row['text'],
                'fontSize' => round($font, 2),
                'fontFamily' => 'Pretendard',
                'bold' => !empty($row['bold']),
                'textAlign' => (string) ($row['align'] ?? 'left'),
                'verticalAlign' => 'middle',
                'backgroundTransparent' => true,
                'textMode' => 'normal',
                'textWrap' => 'char',
            ];
        }

        return [
            'version' => 2,
            'format' => 'labelup',
            'name' => $title,
            'background' => '#FFFFFF',
            'paper' => $paper,
            'pages' => [[
                'index' => 0,
                'cells' => [[
                    'index' => 0,
                    'objects' => $objects,
                ]],
            ]],
            'printOffsetXMm' => 0,
            'printOffsetYMm' => 0,
        ];
    }
}
