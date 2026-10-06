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
            && $forced !== 'chat'
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
            $assistMessages = $messages;
            if ($forced === 'chat') {
                array_unshift($assistMessages, [
                    'role' => 'system',
                    'content' => '사용자는 대화 모드입니다. intent는 chat 또는 recommend_product만 쓰세요. 클립아트·템플릿·이미지 생성은 하지 말고, 용지 추천과 질문에는 텍스트로 답하세요.',
                ]);
            }
            $structured = $this->openai->chatLabelAssist($assistMessages, $catalog);
        }

        $intent = (string) ($structured['intent'] ?? 'chat');
        $translateChoice = $this->resolveTranslateChoice($forced, $this->lastUserText($messages));
        if ($forced === 'translate_yes' || $forced === 'translate_no') {
            $intent = 'generate_template';
        } elseif ($forced === 'chat') {
            if (!in_array($intent, ['chat', 'recommend_product'], true)) {
                $intent = 'chat';
            }
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
            $product = $this->resolveProduct($structured, $catalog, $this->lastUserText($messages));
            if ($product === null) {
                $intent = 'chat';
                $reply .= "\n\n지금은 딱 맞는 등록 상품을 찾지 못했어요. 용도·모양·크기를 조금 더 알려주시면 다시 찾아볼게요.";
            } else {
                if (!str_contains($reply, $product['name'])) {
                    $reply .= "\n\n등록된 라벨 상품 중에서 「{$product['name']}」을(를) 추천드려요. 아래에서 미리보기로 확인해 보세요.";
                }
            }
        } elseif ($intent === 'generate_clipart') {
            $userText = $this->lastUserText($messages);
            $bakeText = $this->wantsBakedTextInImage($userText);
            $prompt = trim((string) ($structured['clipart_prompt'] ?? ''));
            if ($prompt === '') {
                $prompt = $this->fallbackClipartPrompt($messages, $bakeText);
            }
            if ($bakeText) {
                $prompt = $this->ensureBakedTextInImagePrompt($prompt, $userText);
            }
            $clipart = $this->openai->generateClipart($prompt);
            if ($clipart && $userId !== null && $userId > 0) {
                $saved = (new UserAiClipartService())->saveForUser($userId, $clipart);
                $clipartId = $saved > 0 ? $saved : null;
            }
            if ($bakeText) {
                $reply = '요청하신 문구를 그림 안에 넣은 이미지를 만들어 두었어요. 이미지를 눌러 확대해 볼 수 있어요.';
            } elseif (!str_contains($reply, '클립아트') && !str_contains($reply, '이미지')) {
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
            $userText = $this->lastUserText($messages);
            $bakeText = $this->wantsBakedTextInImage($userText);
            $product = $this->resolveProduct($structured, $catalog, $userText);
            $size = $this->resolveTemplateSize($structured, $catalog, $userText, $product);
            $layout = null;
            $translateToKo = $translateChoice === 'translate_yes';

            // HARD RULE: 템플릿의 변경 가능 문구는 반드시 텍스트 오브젝트.
            // 이미지에는 글자·숫자·특수문자를 넣지 않는다.
            // 예외: 사용자가 이미지/그림 "안/속"에 문구를 넣으라고 명시한 경우만 해당 문구를 이미지에 구워 넣는다.
            // (bake여도 나머지 문구 설계·용지 추천은 항상 수행한다.)
            if ($hasImage) {
                try {
                    $layout = $this->openai->extractLabelLayout($messages, $translateToKo);
                } catch (RuntimeException) {
                    $layout = null;
                }
            }
            if (!is_array($layout) || (($layout['texts'] ?? []) === [] && ($layout['codes'] ?? []) === [])) {
                try {
                    $layout = $this->openai->planEditableLabelTemplate(
                        $messages,
                        $translateToKo,
                        trim((string) ($structured['clipart_prompt'] ?? '')) !== ''
                            ? (string) $structured['message'] . ' ' . $userText
                            : $userText,
                        $size['width_mm'],
                        $size['height_mm']
                    );
                } catch (RuntimeException) {
                    $layout = null;
                }
            }
            if (!is_array($layout) || (($layout['texts'] ?? []) === [] && ($layout['codes'] ?? []) === [])) {
                $layout = [
                    'title' => '라비가 만든 라벨 템플릿',
                    'width_mm' => $size['width_mm'],
                    'height_mm' => $size['height_mm'],
                    'background_prompt' => '',
                    'texts' => [[
                        'text' => trim(mb_substr($userText !== '' ? $userText : '상품명', 0, 24)),
                        'x' => 0.08,
                        'y' => 0.28,
                        'w' => 0.84,
                        'h' => 0.22,
                        'font_size_mm' => 5.5,
                        'bold' => true,
                        'align' => 'center',
                        'color' => '#7B2840',
                    ]],
                    'codes' => [],
                ];
            }

            // 이미 용도를 맞춰 고른 용지가 있으면 레이아웃이 70×36 같은 기본값으로 덮지 않는다.
            if ($product === null) {
                $lw = (float) ($layout['width_mm'] ?? 0);
                $lh = (float) ($layout['height_mm'] ?? 0);
                if ($lw >= 15 && $lh >= 15) {
                    $size = [
                        'width_mm' => $this->clampMm($lw, 20, 210),
                        'height_mm' => $this->clampMm($lh, 15, 297),
                    ];
                    $near = $this->bestCatalogMatch(
                        $catalog,
                        $this->inferPaperNeed($userText),
                        $size['width_mm'],
                        $size['height_mm'],
                        $this->hintTokens($userText)
                    );
                    if ($near !== null) {
                        $found = $this->shop->findActiveProduct((int) $near['id']);
                        if ($found && ($found['status'] ?? '') === 'active') {
                            $product = $this->presentProduct($found);
                            if (($product['width_mm'] ?? null) && ($product['height_mm'] ?? null)) {
                                $size = [
                                    'width_mm' => (float) $product['width_mm'],
                                    'height_mm' => (float) $product['height_mm'],
                                ];
                            }
                        }
                    }
                }
            }

            $texts = is_array($layout['texts'] ?? null) ? $layout['texts'] : [];
            $codes = $this->mergeEditableCodes(
                is_array($layout['codes'] ?? null) ? $layout['codes'] : [],
                $this->inferCodesFromUserText($userText)
            );
            $bgHint = trim((string) ($layout['background_prompt'] ?? ''));
            if ($bgHint === '') {
                $bgHint = trim((string) ($structured['clipart_prompt'] ?? ''));
            }
            if ($bgHint === '') {
                $bgHint = $this->fallbackTemplatePrompt($messages, false);
            }
            $baked = $bakeText ? $this->extractBakedTextPhrases($userText) : [];
            // 구울 구체 문구가 없으면 bake를 취소하고 일반 편집 템플릿 경로를 유지
            if ($bakeText && $baked === []) {
                $bakeText = false;
            }
            if ($bakeText) {
                $texts = $this->filterTextsExcludingBaked($texts, $baked);
                // 전부 걸러져도 최소 1개 편집 텍스트는 유지(빈 문서 방지) — codes만 있으면 예외
                if ($texts === [] && $codes === [] && is_array($layout['texts'] ?? null) && ($layout['texts'] ?? []) !== []) {
                    $texts = [$layout['texts'][0]];
                }
                $prompt = $this->ensureBakedTextInImagePrompt(
                    $bgHint
                        . ' Full-bleed print-ready label BACKGROUND artwork, filling the entire canvas edge to edge.'
                        . ' Keep decorations and leave room for separate editable text overlays except for the baked lettering below.'
                        . ' No mockup, no table, no torn paper, no extra background around the label.'
                        . ' Transparent PNG: any area that is not printed artwork must be alpha-transparent.',
                    $userText
                );
            } else {
                $prompt = $bgHint
                    . ' Full-bleed print-ready label BACKGROUND only, filling the entire canvas edge to edge.'
                    . ' Absolutely NO letters, NO numbers, NO digits, NO punctuation, NO words, NO watermarks, NO barcodes, NO QR codes.'
                    . ' Keep colors, shapes, ornaments, patterns, borders, and blank areas where text belonged.'
                    . ' No mockup, no table, no torn paper, no extra background around the label.'
                    . ' Transparent PNG: any area that is not printed artwork must be alpha-transparent.';
            }

            $image = $this->openai->generateClipart($prompt, false);
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
                $texts,
                $product,
                $codes
            );
            if ($bakeText) {
                $reply = '요청하신 문구를 이미지 안에 넣고 라벨 템플릿을 만들었어요. 바로편집에서 확인해 보세요.';
            } elseif ($translateChoice === 'translate_yes') {
                $reply = '이미지의 외국어를 한국어로 번역해, 글자는 편집 가능한 텍스트로 분리한 라벨 템플릿을 만들었어요. 바로편집에서 문구를 바꿔 보세요.';
            } elseif ($codes !== []) {
                $reply = '요청하신 QR·바코드를 실제 코드 객체로 넣고, 글자는 편집 가능한 텍스트로 분리한 라벨 템플릿이에요. 바로편집에서 값과 문구를 바꿔 보세요.';
            } else {
                $reply = '글자·숫자·특수문자는 편집 가능한 텍스트로, 배경만 이미지로 만든 라벨 템플릿이에요. 바로편집에서 문구를 바꿔 보세요.';
            }
            if ($product) {
                $paperLabel = trim((string) ($product['name'] ?? ''));
                $spec = trim((string) ($product['spec'] ?? ''));
                $why = $paperLabel !== '' ? $paperLabel : $spec;
                if ($why !== '') {
                    $need = $this->inferPaperNeed($userText);
                    $reason = $need['kind'] === 'food_container'
                        ? '반찬통·용기처럼 작은 스티커에 맞춰'
                        : '요청하신 용도에 맞춰';
                    $reply .= "\n\n{$reason} 「{$why}」 용지를 골랐어요.";
                }
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
            'chat',
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
                'width_mm' => $w !== null ? (float) $w : 0.0,
                'height_mm' => $h !== null ? (float) $h : 0.0,
                'labels_per_sheet' => isset($row['labels_per_sheet']) ? (int) $row['labels_per_sheet'] : 0,
            ];
        }

        return $items;
    }

    /**
     * @param array<string, mixed> $structured
     * @param array<int, array{id:int, name:string, sku:string, category:string, shape:string, size:string, material:string}> $catalog
     * @return ?array<string, mixed>
     */
    private function resolveProduct(array $structured, array $catalog, string $hint = ''): ?array
    {
        $hint = trim($hint . ' ' . (string) ($structured['search_query'] ?? ''));
        $need = $this->inferPaperNeed($hint);
        $tokens = $this->hintTokens($hint);
        $mentioned = $this->mentionedSizeMm($hint);
        $wantW = $mentioned[0] ?? ((float) ($structured['width_mm'] ?? 0) ?: null);
        $wantH = $mentioned[1] ?? ((float) ($structured['height_mm'] ?? 0) ?: null);
        if ($wantW !== null && $wantW < 8) {
            $wantW = null;
        }
        if ($wantH !== null && $wantH < 8) {
            $wantH = null;
        }

        $ranked = [];
        foreach ($catalog as $item) {
            if (!is_array($item)) {
                continue;
            }
            $ranked[] = [
                'item' => $item,
                'score' => $this->scorePaper($item, $need, $wantW, $wantH, $tokens),
            ];
        }
        usort($ranked, static fn (array $a, array $b): int => $b['score'] <=> $a['score']);
        $best = $ranked[0] ?? null;

        $productId = (int) ($structured['product_id'] ?? 0);
        $gptRank = null;
        if ($productId > 0) {
            foreach ($ranked as $row) {
                if ((int) ($row['item']['id'] ?? 0) === $productId) {
                    $gptRank = $row;
                    break;
                }
            }
        }

        $pick = null;
        if ($gptRank !== null && ($best === null || (int) $gptRank['score'] >= (int) $best['score'] - 8)) {
            $pick = $gptRank['item'];
        } elseif ($best !== null && (int) $best['score'] >= 5) {
            $pick = $best['item'];
        } elseif ($gptRank !== null) {
            $pick = $gptRank['item'];
        }

        if ($pick === null) {
            $query = trim((string) ($structured['search_query'] ?? ''));
            if ($query !== '') {
                $found = $this->shop->activeProducts(['q' => $query], 1, 12);
                $bestRow = null;
                $bestScore = 4;
                foreach ($found['items'] as $row) {
                    if (($row['status'] ?? '') !== 'active') {
                        continue;
                    }
                    $score = $this->scorePaper($row, $need, $wantW, $wantH, $tokens);
                    if ($score > $bestScore) {
                        $bestScore = $score;
                        $bestRow = $row;
                    }
                }
                if ($bestRow !== null) {
                    return $this->presentProduct($bestRow);
                }
            }
            return null;
        }

        $product = $this->shop->findActiveProduct((int) $pick['id']);
        if ($product && ($product['status'] ?? '') === 'active') {
            return $this->presentProduct($product);
        }

        return null;
    }

    /**
     * @return array{
     *   kind:string,
     *   prefer_round:bool,
     *   prefer_waterproof:bool,
     *   min_mm:float,
     *   max_mm:float,
     *   default_w:float,
     *   default_h:float
     * }
     */
    private function inferPaperNeed(string $text): array
    {
        $t = trim($text);
        $foodContainer = (bool) preg_match('/반찬통|밀폐|용기|뚜껑|병뚜껑|잼병|도시락|김치통|원형|동그란|원스티커|인덱싱/u', $t);
        $food = $foodContainer || (bool) preg_match('/반찬|식품|음식|냉장고|냉동|주방|키친/u', $t);
        $shipping = (bool) preg_match('/주소|택배|배송|수취|송장/u', $t);
        $barcode = (bool) preg_match('/바코드|피킹|SKU|sku|재고/u', $t);

        if ($shipping && !$food) {
            return [
                'kind' => 'shipping',
                'prefer_round' => false,
                'prefer_waterproof' => false,
                'min_mm' => 40,
                'max_mm' => 120,
                'default_w' => 100.0,
                'default_h' => 50.0,
            ];
        }
        if ($barcode && !$food) {
            return [
                'kind' => 'barcode',
                'prefer_round' => false,
                'prefer_waterproof' => false,
                'min_mm' => 25,
                'max_mm' => 80,
                'default_w' => 70.0,
                'default_h' => 36.0,
            ];
        }
        if ($foodContainer || $food) {
            $round = $foodContainer || (bool) preg_match('/원형|동그란|뚜껑|병/u', $t);
            return [
                'kind' => 'food_container',
                'prefer_round' => $round,
                'prefer_waterproof' => true,
                'min_mm' => 28,
                'max_mm' => 70,
                'default_w' => $round ? 40.0 : 47.0,
                'default_h' => $round ? 40.0 : 26.9,
            ];
        }

        return [
            'kind' => 'general',
            'prefer_round' => false,
            'prefer_waterproof' => false,
            'min_mm' => 20,
            'max_mm' => 120,
            'default_w' => 50.0,
            'default_h' => 30.0,
        ];
    }

    /**
     * @param array<string, mixed> $row
     * @param array{kind:string,prefer_round:bool,prefer_waterproof:bool,min_mm:float,max_mm:float,default_w:float,default_h:float} $need
     * @param array<int, string> $tokens
     */
    private function scorePaper(array $row, array $need, ?float $wantW = null, ?float $wantH = null, array $tokens = []): int
    {
        $name = (string) ($row['name'] ?? '');
        $sku = (string) ($row['sku'] ?? '');
        $cat = (string) ($row['category'] ?? $row['category_name'] ?? '');
        $shape = strtolower((string) ($row['shape'] ?? ''));
        $mat = (string) ($row['material'] ?? '');
        [$w, $h] = $this->rowSizeMm($row);
        $hay = $name . ' ' . $sku . ' ' . $cat . ' ' . $shape . ' ' . $mat;
        $score = $this->lexicalScore($hay, $tokens, $w, $h, $sku, $shape);

        if ($wantW !== null && $wantH !== null && $w > 0 && $h > 0) {
            $dw = abs($w - $wantW);
            $dh = abs($h - $wantH);
            if ($dw <= 1.2 && $dh <= 1.2) {
                $score += 12;
            } elseif ($dw <= 4 && $dh <= 4) {
                $score += 6;
            }
        } elseif ($wantW !== null && $w > 0 && abs($w - $wantW) <= 1.5) {
            $score += 5;
        }

        if ($need['kind'] === 'food_container') {
            if (preg_match('/주소|택배|배송|바코드|송장/u', $hay)) {
                $score -= 12;
            }
            $isRound = (bool) preg_match('/R\d|R-|-R/i', $sku)
                || in_array($shape, ['ellipse', 'circle', 'round'], true)
                || ($w > 0 && $h > 0 && abs($w - $h) < 1.2);
            if ($need['prefer_round'] && $isRound) {
                $score += 10;
            } elseif ($isRound) {
                $score += 4;
            }
            if (preg_match('/방수|waterproof/iu', $hay)) {
                $score += 6;
            }
            if (preg_match('/인덱싱/u', $hay)) {
                $score += 4;
            }
            if ($w >= $need['min_mm'] && $h >= $need['min_mm'] && $w <= $need['max_mm'] && $h <= $need['max_mm']) {
                $score += 5;
            }
            if (abs($w - 40) < 1.2 && abs($h - 40) < 1.2) {
                $score += 8;
            }
            if (abs($w - 63.5) < 1.2 && abs($h - 63.5) < 1.2) {
                $score += 5;
            }
            if ($w > 90 || $h > 90) {
                $score -= 10;
            }
        } elseif ($need['kind'] === 'shipping') {
            if (preg_match('/주소|택배|배송|물류/u', $hay)) {
                $score += 8;
            }
            if (abs($w - 100) < 3 && abs($h - 50) < 3) {
                $score += 8;
            }
            if (preg_match('/R\d/i', $sku)) {
                $score -= 6;
            }
        } elseif ($need['kind'] === 'barcode') {
            if (preg_match('/바코드|피킹/u', $hay)) {
                $score += 6;
            }
            if (abs($w - 70) < 3 && abs($h - 36) < 3) {
                $score += 6;
            }
        } elseif ($w >= 20 && $h >= 15 && $w <= 120 && $h <= 80) {
            $score += 2;
        }

        if (preg_match('/-100(?:\b|$)/', $sku)) {
            $score -= 1;
        }
        if (preg_match('/-(?:10|20)(?:\b|$)/', $sku)) {
            $score += 1;
        }

        return $score;
    }

    /** @return array<int, string> */
    private function hintTokens(string $text): array
    {
        $text = trim($text);
        if ($text === '') {
            return [];
        }
        $parts = preg_split('/[^\p{L}\p{N}.]+/u', $text) ?: [];
        $stop = [
            '그려줘', '그려', '달라', '주세요', '만들어줘', '만들어', '템플릿', '라벨', '스티커',
            '디자인', '요청', '해줘', '해주세요', '완성', '전체', '이거', '저거', '좀', '용',
            '걸로', '같은', '있는', '없는', '하고', '해서', '바로', '편집',
        ];
        $tokens = [];
        foreach ($parts as $part) {
            $part = trim((string) $part);
            if ($part === '' || mb_strlen($part) < 2 || in_array($part, $stop, true)) {
                continue;
            }
            $tokens[] = $part;
            if (preg_match('/원형|동그란|뚜껑|원스티커/u', $part)) {
                array_push($tokens, '원형', '인덱싱', 'R');
            }
            if (preg_match('/방수|젖|물묻는/u', $part)) {
                $tokens[] = '방수';
            }
            if (preg_match('/투명|클리어/u', $part)) {
                array_push($tokens, '투명', '클리어');
            }
            if (preg_match('/크라프트|크래프트/u', $part)) {
                $tokens[] = '크라프트';
            }
            if (preg_match('/유광|광택/u', $part)) {
                $tokens[] = '유광';
            }
        }

        return array_values(array_unique($tokens));
    }

    /** @return array{0:?float,1:?float} */
    private function mentionedSizeMm(string $text): array
    {
        if (preg_match('/([\d.]+)\s*[×xX]\s*([\d.]+)\s*(?:mm)?/u', $text, $m)) {
            return [(float) $m[1], (float) $m[2]];
        }
        if (preg_match('/([\d.]+)\s*cm/u', $text, $m)) {
            $n = (float) $m[1] * 10;
            return [$n, $n];
        }
        if (preg_match('/([\d.]+)\s*mm/u', $text, $m)) {
            $n = (float) $m[1];
            return [$n, $n];
        }
        return [null, null];
    }

    /** @param array<int, string> $tokens */
    private function lexicalScore(string $hay, array $tokens, float $w, float $h, string $sku, string $shape): int
    {
        if ($tokens === []) {
            return 0;
        }
        $hayLower = mb_strtolower($hay);
        $score = 0;
        foreach ($tokens as $token) {
            $t = mb_strtolower($token);
            if ($t === '') {
                continue;
            }
            if (mb_strlen($t) >= 2 && mb_strpos($hayLower, $t) !== false) {
                $score += mb_strlen($t) >= 3 ? 4 : 2;
            }
        }
        foreach ($tokens as $token) {
            if (preg_match('/원형|동그란|R/u', $token)
                && (preg_match('/R\d|R-|-R/i', $sku) || abs($w - $h) < 1.2 || in_array($shape, ['ellipse', 'circle', 'round'], true))) {
                $score += 3;
                break;
            }
        }
        return $score;
    }

    /** @param array<string, mixed> $row
     *  @return array{0:float,1:float} */
    private function rowSizeMm(array $row): array
    {
        $w = (float) ($row['width_mm'] ?? 0);
        $h = (float) ($row['height_mm'] ?? 0);
        if ($w > 0 && $h > 0) {
            return [$w, $h];
        }
        $size = (string) ($row['size'] ?? '');
        if (preg_match('/([\d.]+)\s*[×xX]\s*([\d.]+)/u', $size, $m)) {
            return [(float) $m[1], (float) $m[2]];
        }
        return [0.0, 0.0];
    }

    /**
     * @param array<int, array<string, mixed>> $catalog
     * @param array{kind:string,prefer_round:bool,prefer_waterproof:bool,min_mm:float,max_mm:float,default_w:float,default_h:float} $need
     * @return ?array<string, mixed>
     */
    private function bestCatalogMatch(array $catalog, array $need, ?float $wantW = null, ?float $wantH = null, array $tokens = []): ?array
    {
        $best = null;
        $bestScore = 3;
        foreach ($catalog as $item) {
            if (!is_array($item)) {
                continue;
            }
            $score = $this->scorePaper($item, $need, $wantW, $wantH, $tokens);
            if ($score > $bestScore) {
                $bestScore = $score;
                $best = $item;
            }
        }
        return $best;
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
            // 편집기 PaperSpec 호환 (label_specs 배치값)
            'paper_size' => self::nullableTrim($product['paper_size'] ?? null),
            'columns_count' => isset($product['columns_count']) ? (int) $product['columns_count'] : null,
            'rows_count' => isset($product['rows_count']) ? (int) $product['rows_count'] : null,
            'top_margin_mm' => isset($product['top_margin_mm']) ? (float) $product['top_margin_mm'] : null,
            'left_margin_mm' => isset($product['left_margin_mm']) ? (float) $product['left_margin_mm'] : null,
            'h_gap_mm' => isset($product['h_gap_mm']) ? (float) $product['h_gap_mm'] : null,
            'v_gap_mm' => isset($product['v_gap_mm']) ? (float) $product['v_gap_mm'] : null,
            'corner_radius_x_mm' => isset($product['corner_radius_x_mm']) ? (float) $product['corner_radius_x_mm'] : null,
            'corner_radius_y_mm' => isset($product['corner_radius_y_mm']) ? (float) $product['corner_radius_y_mm'] : null,
            'label_color' => self::nullableTrim($product['label_color'] ?? null),
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

    private static function nullableTrim(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }
        $s = trim((string) $value);

        return $s === '' ? null : $s;
    }

    /** @param array<int, array{role:string, content:mixed}> $messages */
    private function fallbackClipartPrompt(array $messages, bool $bakeText = false): string
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
        if ($bakeText) {
            return "Simple clean label clipart illustration for sticker printing, fully transparent background, isolated centered motif inspired by: {$hint}. Flat vector style, high contrast, no watermark, no white or black studio backdrop."
                . $this->bakedTextInstruction($lastUser);
        }

        return "Simple clean label clipart illustration for sticker printing, fully transparent background, isolated centered motif inspired by: {$hint}. Flat vector style, high contrast, no text, no watermark, no white or black studio backdrop.";
    }

    /** @param array<int, array{role:string, content:mixed}> $messages */
    private function fallbackTemplatePrompt(array $messages, bool $bakeText = false): string
    {
        $hint = trim(mb_substr($this->lastUserText($messages) !== '' ? $this->lastUserText($messages) : 'product label', 0, 120));
        if ($bakeText) {
            return "Print-ready full-bleed label artwork for sticker printing, filling the entire canvas edge to edge. Soft packaging-style colors inspired by: {$hint}. Decorations, shapes, borders, patterns, and the requested lettering. No mockup, no wooden table, no torn paper, no extra background around the label."
                . $this->bakedTextInstruction($this->lastUserText($messages));
        }

        return "Print-ready full-bleed label BACKGROUND only for sticker printing, filling the entire canvas edge to edge. Soft packaging-style colors inspired by: {$hint}. Decorations, shapes, borders, patterns only. Absolutely NO letters, NO numbers, NO digits, NO words, NO watermarks. No mockup, no wooden table, no torn paper, no extra background around the label.";
    }

    /**
     * 사용자가 이미지/그림 안·속에 글자·이름을 직접 넣으라고 명시한 경우만 true.
     * 「문구 넣어서 템플릿 만들어줘」처럼 일반 편집 템플릿 요청은 false.
     * 예: 꽃그림에 '이중은' 이름 넣어서 이미지 만들어줘
     */
    private function wantsBakedTextInImage(string $text): bool
    {
        $t = trim($text);
        if ($t === '') {
            return false;
        }
        // 편집 가능 분리·글자 제외를 명시한 경우는 bake 하지 않음
        if (preg_match('/(글자|텍스트|문구).{0,8}(빼|없이|말고|제외)|편집\s*가능|텍스트\s*오브젝트|텍스트로\s*분리/u', $t)) {
            return false;
        }

        $mediaInside = (bool) preg_match('/(이미지|그림|사진|일러스트|클립아트).{0,20}(안|속)/u', $t)
            || (bool) preg_match('/(안|속).{0,12}(에\s*)?(이미지|그림|사진|일러스트|클립아트)/u', $t);
        $wantsTemplate = (bool) preg_match('/템플릿|라벨\s*디자인|완성\s*(된\s*)?라벨|편집기/u', $t);
        // 템플릿 요청인데 "그림/이미지 안" 표현이 없으면 편집 가능 텍스트 경로
        if ($wantsTemplate && !$mediaInside) {
            return false;
        }

        $patterns = [
            '/(이미지|그림|사진|일러스트|클립아트).{0,16}(안|속).{0,24}(이름|글자|텍스트|문구|문자|워딩)/u',
            '/(이름|글자|텍스트|문구|문자|워딩).{0,24}(이미지|그림|사진|일러스트|클립아트).{0,12}(안|속)/u',
            '/(이미지|그림|사진|일러스트|클립아트).{0,20}(에|으로|위에).{0,16}(이름|글자|텍스트|문구).{0,12}(넣|박아|그려|써\s*넣|포함)/u',
        ];
        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $t)) {
                return true;
            }
        }

        // 따옴표 이름 + 그림/이미지 만들기 (템플릿 키워드 없을 때만)
        if (
            !$wantsTemplate
            && preg_match('/[\'"`「『][^\'"`」』]{1,40}[\'"`」』]/u', $t)
            && preg_match('/(그림|이미지|사진|일러스트).{0,24}(넣|만들|그려)/u', $t)
        ) {
            return true;
        }

        return false;
    }

    /** @return list<string> */
    private function extractBakedTextPhrases(string $userText): array
    {
        $parts = [];
        if (preg_match_all('/[\'"`「『]([^\'"`」』]{1,40})[\'"`」』]/u', $userText, $m)) {
            foreach ($m[1] as $q) {
                $q = trim((string) $q);
                if ($q !== '') {
                    $parts[] = $q;
                }
            }
        }
        if (preg_match_all('/이름\s*[\'"`「『]?([가-힣A-Za-z0-9·\.\-]{1,24})[\'"`」』]?/u', $userText, $m2)) {
            foreach ($m2[1] as $q) {
                $q = trim((string) $q);
                if ($q !== '' && !in_array($q, ['넣', '넣어', '넣어서', '넣고'], true)) {
                    $parts[] = $q;
                }
            }
        }

        $out = [];
        foreach ($parts as $p) {
            if ($p === '') {
                continue;
            }
            $dup = false;
            foreach ($out as $existing) {
                if (mb_strtolower($existing) === mb_strtolower($p)) {
                    $dup = true;
                    break;
                }
            }
            if (!$dup) {
                $out[] = $p;
            }
        }

        return $out;
    }

    private function bakedTextInstruction(string $userText): string
    {
        $phrases = $this->extractBakedTextPhrases($userText);
        if ($phrases === []) {
            return ' The artwork MUST clearly render the exact Korean or English text the user asked to put in the image. Keep spelling exact, legible, and integrated into the design. Do not omit the lettering.';
        }
        $joined = implode(', ', array_map(static fn (string $s): string => '"' . $s . '"', $phrases));

        return " The artwork MUST clearly render this exact text on the image (legible, correctly spelled Korean/English lettering, integrated into the design): {$joined}. Do not omit, translate away, or misspell the text.";
    }

    private function ensureBakedTextInImagePrompt(string $prompt, string $userText): string
    {
        $clean = preg_replace(
            '/\b(absolutely\s+)?(no|without|zero)\s+(letters?|numbers?|digits?|words?|text|texts?|watermarks?|punctuation)(\s*,\s*(no\s+)?(letters?|numbers?|digits?|words?|text|watermarks?|punctuation))*\b[^.!]*/i',
            '',
            $prompt
        ) ?? $prompt;
        $clean = preg_replace('/\bno text\b[^.!]*/i', '', $clean) ?? $clean;
        $clean = trim(preg_replace('/\s{2,}/', ' ', $clean) ?? $clean);
        $clean = rtrim($clean, " \t\n\r.,;");
        $instr = $this->bakedTextInstruction($userText);
        if (!preg_match('/must clearly render/i', $clean)) {
            $clean .= '.' . $instr;
        }

        return $clean;
    }

    /**
     * @param array<int, array<string, mixed>> $texts
     * @param list<string> $baked
     * @return array<int, array<string, mixed>>
     */
    private function filterTextsExcludingBaked(array $texts, array $baked): array
    {
        if ($baked === [] || $texts === []) {
            return $texts;
        }
        $out = [];
        foreach ($texts as $row) {
            if (!is_array($row)) {
                continue;
            }
            $t = trim((string) ($row['text'] ?? ''));
            if ($t === '') {
                continue;
            }
            $skip = false;
            foreach ($baked as $phrase) {
                if ($phrase !== '' && mb_stripos($t, $phrase) !== false) {
                    $skip = true;
                    break;
                }
                if ($phrase !== '' && mb_stripos($phrase, $t) !== false && mb_strlen($t) >= 2) {
                    $skip = true;
                    break;
                }
            }
            if (!$skip) {
                $out[] = $row;
            }
        }

        return $out;
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
     * @param array<string, mixed>|null $product
     * @return array{width_mm:float, height_mm:float}
     */
    private function resolveTemplateSize(array $structured, array $catalog, string $hint = '', ?array $product = null): array
    {
        if ($product && ($product['width_mm'] ?? null) && ($product['height_mm'] ?? null)) {
            return [
                'width_mm' => (float) $product['width_mm'],
                'height_mm' => (float) $product['height_mm'],
            ];
        }

        $need = $this->inferPaperNeed($hint);
        $w = (float) ($structured['width_mm'] ?? 0);
        $h = (float) ($structured['height_mm'] ?? 0);
        if ($w >= 15 && $h >= 15 && $this->sizeFitsNeed($w, $h, $need)) {
            return [
                'width_mm' => $this->clampMm($w, 20, 210),
                'height_mm' => $this->clampMm($h, 15, 297),
            ];
        }

        return [
            'width_mm' => $need['default_w'],
            'height_mm' => $need['default_h'],
        ];
    }

    /** @param array{kind:string,min_mm:float,max_mm:float} $need */
    private function sizeFitsNeed(float $w, float $h, array $need): bool
    {
        if ($need['kind'] === 'food_container') {
            $max = max($w, $h);
            $min = min($w, $h);
            if ($max > $need['max_mm'] + 8) {
                return false;
            }
            if ($min < 20) {
                return false;
            }
            if (abs($w - 70) < 1.5 && abs($h - 36) < 1.5) {
                return false;
            }
            if (abs($w - 100) < 2 && abs($h - 50) < 2) {
                return false;
            }
        }
        return $w <= 120 && $h <= 120;
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
     * @param array<string, mixed>|null $paper
     * @return array<string, mixed>
     */
    private function presentTemplate(array $image, float $widthMm, float $heightMm, array $texts = [], ?array $paper = null, array $codes = []): array
    {
        $title = (string) ($image['title'] ?? '라비가 만든 라벨 템플릿');
        $url = (string) ($image['url'] ?? '');
        $w = max(10.0, $widthMm);
        $h = max(10.0, $heightMm);
        $paperMeta = $this->templatePaperMeta($paper, $w, $h);

        if (($texts !== [] || $codes !== []) && $url !== '') {
            $document = $this->buildEditableTemplateDocument(
                $url,
                $title,
                $w,
                $h,
                $texts,
                $paper,
                $codes
            );
            $editorQuery = ['labiDoc' => '1'];
            if (($paperMeta['sku'] ?? '') !== '') {
                $editorQuery['sku'] = $paperMeta['sku'];
            }
            if ($w > 0 && $h > 0) {
                $editorQuery['w'] = rtrim(rtrim(sprintf('%.2f', $w), '0'), '.');
                $editorQuery['h'] = rtrim(rtrim(sprintf('%.2f', $h), '0'), '.');
            }
            $editorUrl = url('editor/') . '?' . http_build_query($editorQuery);

            return array_merge([
                'url' => $url,
                'prompt' => (string) ($image['prompt'] ?? ''),
                'title' => $title,
                'width_mm' => $w,
                'height_mm' => $h,
                'fit' => 'cover',
                'editor_url' => $editorUrl,
                'document' => $document,
                'editable_texts' => count($texts),
                'editable_codes' => count($codes),
            ], $paperMeta);
        }

        $query = [
            'w' => rtrim(rtrim(sprintf('%.2f', $w), '0'), '.'),
            'h' => rtrim(rtrim(sprintf('%.2f', $h), '0'), '.'),
            'name' => $title,
            'clipart' => $url,
            'fit' => 'cover',
        ];
        if (($paperMeta['sku'] ?? '') !== '') {
            $query['sku'] = $paperMeta['sku'];
        }
        $editorUrl = url('editor/') . '?' . http_build_query($query);

        return array_merge([
            'url' => $url,
            'prompt' => (string) ($image['prompt'] ?? ''),
            'title' => $title,
            'width_mm' => $w,
            'height_mm' => $h,
            'fit' => 'cover',
            'editor_url' => $editorUrl,
        ], $paperMeta);
    }

    /**
     * @param array<string, mixed>|null $paper
     * @return array{paper_name:string, sku:string, labels_per_page:int, use_case_label:string}
     */
    private function templatePaperMeta(?array $paper, float $widthMm, float $heightMm): array
    {
        $name = trim((string) ($paper['name'] ?? ''));
        $sku = trim((string) ($paper['sku'] ?? ''));
        $labels = (int) ($paper['labels_per_sheet'] ?? 0);
        $spec = trim((string) ($paper['spec'] ?? ''));
        if ($name === '' && $widthMm > 0 && $heightMm > 0) {
            $name = rtrim(rtrim(sprintf('%.1f', $widthMm), '0'), '.') . '×'
                . rtrim(rtrim(sprintf('%.1f', $heightMm), '0'), '.') . 'mm';
        }

        return [
            'paper_name' => $name !== '' ? $name : $spec,
            'sku' => $sku,
            'labels_per_page' => $labels,
            'use_case_label' => '',
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
     * @param array<string, mixed>|null $paperProduct presentProduct() 결과(용지 배치값 포함)
     * @param array<int, array{
     *   kind:string,
     *   value:string,
     *   x:float,
     *   y:float,
     *   w:float,
     *   h:float,
     *   show_text:bool
     * }> $codes
     * @return array<string, mixed>
     */
    private function buildEditableTemplateDocument(
        string $imageUrl,
        string $title,
        float $w,
        float $h,
        array $texts,
        ?array $paperProduct = null,
        array $codes = []
    ): array {
        $paper = $this->resolveDocumentPaper($w, $h, $paperProduct);
        $lw = max(1.0, (float) ($paper['labelWidthMm'] ?? $w));
        $lh = max(1.0, (float) ($paper['labelHeightMm'] ?? $h));

        $objects = [
            $this->labiImageObject('labiBg01', 0, 0, $lw, $lh, $imageUrl, 0, 'cover'),
        ];

        $z = 1;
        foreach ($texts as $i => $row) {
            $boxW = max(4.0, (float) $row['w'] * $lw);
            $boxH = max(3.0, (float) $row['h'] * $lh);
            $x = max(0.0, min($lw - 2.0, (float) $row['x'] * $lw));
            $y = max(0.0, min($lh - 2.0, (float) $row['y'] * $lh));
            if ($x + $boxW > $lw) {
                $boxW = max(3.0, $lw - $x);
            }
            if ($y + $boxH > $lh) {
                $boxH = max(2.5, $lh - $y);
            }
            $font = (float) ($row['font_size_mm'] ?? 0);
            if ($font < 1.5) {
                $font = max(1.8, min(12.0, $boxH * 0.72));
            }
            $font = max(1.5, min(14.0, $font));
            if ($font > $boxH * 1.15) {
                $font = max(1.5, $boxH * 0.78);
            }

            $objects[] = $this->labiTextObject(
                sprintf('labiTx%02d', $i + 1),
                $x,
                $y,
                $boxW,
                $boxH,
                (string) $row['text'],
                (string) ($row['color'] ?? '#2E2A27'),
                $font,
                !empty($row['bold']),
                (string) ($row['align'] ?? 'left'),
                $z++
            );
        }

        foreach ($codes as $i => $code) {
            $kind = strtolower(trim((string) ($code['kind'] ?? '')));
            $value = trim((string) ($code['value'] ?? ''));
            if ($value === '' || !in_array($kind, ['qr', 'barcode'], true)) {
                continue;
            }
            $boxW = max(6.0, (float) $code['w'] * $lw);
            $boxH = max(6.0, (float) $code['h'] * $lh);
            $x = max(0.0, min($lw - 2.0, (float) $code['x'] * $lw));
            $y = max(0.0, min($lh - 2.0, (float) $code['y'] * $lh));
            if ($kind === 'qr') {
                $side = max(8.0, min($boxW, $boxH, $lw - $x, $lh - $y));
                $boxW = $side;
                $boxH = $side;
                $objects[] = $this->labiQrObject(
                    sprintf('labiQr%02d', $i + 1),
                    $x,
                    $y,
                    $boxW,
                    $value,
                    $z++
                );
            } else {
                if ($x + $boxW > $lw) {
                    $boxW = max(8.0, $lw - $x);
                }
                if ($y + $boxH > $lh) {
                    $boxH = max(6.0, $lh - $y);
                }
                $objects[] = $this->labiBarcodeObject(
                    sprintf('labiBc%02d', $i + 1),
                    $x,
                    $y,
                    $boxW,
                    $boxH,
                    $value,
                    !empty($code['show_text']),
                    $z++
                );
            }
        }

        $cellCount = max(1, (int) ($paper['columns'] ?? 1) * (int) ($paper['rows'] ?? 1));
        if (!empty($paper['customSlots']) && is_array($paper['customSlots'])) {
            $cellCount = max(1, count($paper['customSlots']));
        }
        $cells = [];
        for ($c = 0; $c < $cellCount; $c++) {
            $cells[] = [
                'index' => $c,
                'objects' => $c === 0 ? $objects : [],
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
                'cells' => $cells,
            ]],
            'printOffsetXMm' => 0,
            'printOffsetYMm' => 0,
        ];
    }

    /**
     * 편집기 PaperSpec JSON (시드·paperData·DB 규격과 동일 키).
     *
     * @param array<string, mixed>|null $product
     * @return array<string, mixed>
     */
    private function resolveDocumentPaper(float $labelW, float $labelH, ?array $product): array
    {
        $product = $product ?? [];
        $sku = trim((string) ($product['sku'] ?? ''));
        $name = trim((string) ($product['name'] ?? $product['paper_name'] ?? ''));

        if ($sku !== '') {
            $fromFile = $this->loadPaperDataJson($sku);
            if ($fromFile !== null) {
                if ($name !== '') {
                    $fromFile['name'] = $name;
                }
                return $fromFile;
            }
        }

        $cols = (int) ($product['columns_count'] ?? 0);
        $rows = (int) ($product['rows_count'] ?? 0);
        $labels = (int) ($product['labels_per_sheet'] ?? 0);
        $lw = $labelW > 0 ? $labelW : (float) ($product['width_mm'] ?? 70);
        $lh = $labelH > 0 ? $labelH : (float) ($product['height_mm'] ?? 36);
        $lw = max(1.0, $lw);
        $lh = max(1.0, $lh);

        if ($cols < 1 && $rows < 1 && $labels > 1) {
            $cols = max(1, (int) ceil(sqrt($labels)));
            $rows = max(1, (int) ceil($labels / $cols));
        }
        if ($cols < 1) {
            $cols = 1;
        }
        if ($rows < 1) {
            $rows = 1;
        }

        $hGap = isset($product['h_gap_mm']) ? max(0.0, (float) $product['h_gap_mm']) : ($cols > 1 ? 5.0 : 0.0);
        $vGap = isset($product['v_gap_mm']) ? max(0.0, (float) $product['v_gap_mm']) : ($rows > 1 ? 3.0 : 0.0);
        $left = isset($product['left_margin_mm']) ? max(0.0, (float) $product['left_margin_mm']) : null;
        $top = isset($product['top_margin_mm']) ? max(0.0, (float) $product['top_margin_mm']) : null;

        $usedW = $lw * $cols + $hGap * max(0, $cols - 1);
        $usedH = $lh * $rows + $vGap * max(0, $rows - 1);
        $pageW = 210.0;
        $pageH = 297.0;
        $paperSize = strtoupper(trim((string) ($product['paper_size'] ?? 'A4')));
        if ($paperSize === 'LETTER') {
            $pageW = 215.9;
            $pageH = 279.4;
        }
        if ($left === null) {
            $left = max(0.0, ($pageW - $usedW) / 2);
        }
        if ($top === null) {
            $top = max(0.0, ($pageH - $usedH) / 2);
        }
        $needW = $usedW + $left;
        $needH = $usedH + $top;
        if ($needW > $pageW + 0.5) {
            $pageW = $needW;
        }
        if ($needH > $pageH + 0.5) {
            $pageH = $needH;
        }
        $right = max(0.0, $pageW - $left - $usedW);
        $bottom = max(0.0, $pageH - $top - $usedH);

        $shapeKind = 'rect';
        $rawShape = strtolower(trim((string) ($product['shape'] ?? '')));
        if (str_contains($rawShape, 'round') || str_contains($rawShape, '모서')) {
            $shapeKind = 'roundrect';
        } elseif (str_contains($rawShape, 'circle') || str_contains($rawShape, 'ellipse') || str_contains($rawShape, '원')) {
            $shapeKind = abs($lw - $lh) < 0.8 ? 'circle' : 'ellipse';
        }
        $shape = ['kind' => $shapeKind];
        $rx = isset($product['corner_radius_x_mm']) ? (float) $product['corner_radius_x_mm'] : null;
        $ry = isset($product['corner_radius_y_mm']) ? (float) $product['corner_radius_y_mm'] : null;
        if ($shapeKind === 'roundrect') {
            $shape['cornerRadiusMm'] = $rx !== null && $rx > 0 ? $rx : 1.5;
            if ($ry !== null && $ry > 0) {
                $shape['cornerRadiusYMm'] = $ry;
            }
        }

        $labelColor = trim((string) ($product['label_color'] ?? ''));
        if ($labelColor === '') {
            $labelColor = '#FFFFFF';
        }

        return [
            'version' => 1,
            'paperNo' => $sku !== '' ? $sku : 'LU-AI',
            'name' => $name !== '' ? $name : sprintf(
                '%s×%s mm',
                rtrim(rtrim(sprintf('%.1f', $lw), '0'), '.'),
                rtrim(rtrim(sprintf('%.1f', $lh), '0'), '.')
            ),
            'category' => $sku !== '' ? 'A4' : 'Custom',
            'brand' => 'LabelUp',
            'paperWidthMm' => round($pageW, 2),
            'paperHeightMm' => round($pageH, 2),
            'labelWidthMm' => round($lw, 2),
            'labelHeightMm' => round($lh, 2),
            'columns' => $cols,
            'rows' => $rows,
            'leftMarginMm' => round($left, 2),
            'topMarginMm' => round($top, 2),
            'rightMarginMm' => round($right, 2),
            'bottomMarginMm' => round($bottom, 2),
            'hGapMm' => round($hGap, 2),
            'vGapMm' => round($vGap, 2),
            'labelColor' => $labelColor,
            'shape' => $shape,
        ];
    }

    /** @return array<string, mixed>|null */
    private function loadPaperDataJson(string $sku): ?array
    {
        $sku = trim($sku);
        if ($sku === '' || !preg_match('/^[A-Za-z0-9_-]+$/', $sku)) {
            return null;
        }
        $candidates = [
            public_path('editor/paperData/' . $sku . '.json'),
            base_path('editor-src/LabelUp.Editor/wwwroot/paperData/' . $sku . '.json'),
        ];
        foreach ($candidates as $path) {
            if (!is_file($path)) {
                continue;
            }
            $raw = @file_get_contents($path);
            if (!is_string($raw) || trim($raw) === '') {
                continue;
            }
            $decoded = json_decode($raw, true);
            if (!is_array($decoded) || empty($decoded['paperNo'])) {
                continue;
            }
            return $decoded;
        }
        return null;
    }

    /** @return array<string, mixed> */
    private function labiImageObject(
        string $id,
        float $x,
        float $y,
        float $w,
        float $h,
        string $url,
        int $z,
        string $fit = 'cover'
    ): array {
        return [
            'id' => $id,
            'type' => 'image',
            'zIndex' => $z,
            'locked' => false,
            'visible' => true,
            'x' => round($x, 2),
            'y' => round($y, 2),
            'width' => round($w, 2),
            'height' => round($h, 2),
            'rotation' => 0,
            'fill' => 'transparent',
            'stroke' => 'transparent',
            'strokeWidth' => 0,
            'opacity' => 1,
            'imageData' => $url,
            'imageFit' => $fit === 'contain' ? 'contain' : 'cover',
            'lockAspectRatio' => false,
            'backgroundTransparent' => true,
            'backgroundFill' => 'transparent',
        ];
    }

    /** @return array<string, mixed> */
    private function labiTextObject(
        string $id,
        float $x,
        float $y,
        float $w,
        float $h,
        string $text,
        string $fill,
        float $fontSize,
        bool $bold,
        string $align,
        int $z
    ): array {
        return [
            'id' => $id,
            'type' => 'text',
            'zIndex' => $z,
            'locked' => false,
            'visible' => true,
            'x' => round($x, 2),
            'y' => round($y, 2),
            'width' => round($w, 2),
            'height' => round($h, 2),
            'rotation' => 0,
            'fill' => $fill !== '' ? $fill : '#2E2A27',
            'stroke' => 'transparent',
            'strokeWidth' => 0,
            'opacity' => 1,
            'text' => $text,
            'fontSize' => round($fontSize, 2),
            'fontFamily' => 'Pretendard',
            'bold' => $bold,
            'italic' => false,
            'underline' => false,
            'textAlign' => in_array($align, ['left', 'center', 'right'], true) ? $align : 'left',
            'verticalAlign' => 'middle',
            'lineHeight' => 1.15,
            'letterSpacing' => 0,
            'textDirection' => 'horizontal',
            'textWrap' => 'char',
            'backgroundTransparent' => true,
            'backgroundFill' => 'transparent',
            'textMode' => 'normal',
            'wordArtStyle' => 'none',
            'customKind' => 'none',
        ];
    }

    /**
     * 사용자 요청에서 URL·SKU 등 인코딩 값을 추출해 QR/바코드 후보를 만든다.
     *
     * @return array<int, array{kind:string,value:string,x:float,y:float,w:float,h:float,show_text:bool}>
     */
    private function inferCodesFromUserText(string $text): array
    {
        $text = trim($text);
        if ($text === '') {
            return [];
        }
        $wantsQr = (bool) preg_match('/QR|큐알|큐\s*아르|이차원\s*코드|qr\s*코드/iu', $text);
        $wantsBarcode = (bool) preg_match('/바코드|barcode|code\s*[- ]?128|ean[- ]?13|upc/iu', $text);
        $codes = [];

        if (preg_match_all('#https?://[^\s<>"\']+#iu', $text, $m)) {
            foreach ($m[0] as $raw) {
                $url = rtrim($raw, '.,;)]}>"\'');
                if ($url === '') {
                    continue;
                }
                // URL은 기본적으로 QR. 바코드만 명시하고 QR이 없으면 스킵.
                if ($wantsBarcode && !$wantsQr) {
                    continue;
                }
                $codes[] = [
                    'kind' => 'qr',
                    'value' => $url,
                    'x' => 0.72,
                    'y' => 0.62,
                    'w' => 0.22,
                    'h' => 0.22,
                    'show_text' => false,
                ];
            }
        }

        if ($wantsQr && $codes === [] && preg_match('/(?:QR|큐알)[^\w가-힣]{0,6}([a-z0-9][a-z0-9._~:/?#\[\]@!$&\'()*+,;=%-]{5,})/iu', $text, $m)) {
            $val = rtrim($m[1], '.,;)]}>"\'');
            if ($val !== '') {
                if (!preg_match('#^https?://#i', $val) && preg_match('/\./', $val)) {
                    $val = 'https://' . $val;
                }
                $codes[] = [
                    'kind' => 'qr',
                    'value' => $val,
                    'x' => 0.72,
                    'y' => 0.62,
                    'w' => 0.22,
                    'h' => 0.22,
                    'show_text' => false,
                ];
            }
        }

        if ($wantsBarcode) {
            $barcodeVal = '';
            if (preg_match('/(?:바코드|barcode|ean|upc|sku)\s*[:：]?\s*([A-Za-z0-9\-_]{6,32})/iu', $text, $m)) {
                $barcodeVal = $m[1];
            } elseif (preg_match('/\b(\d{8}|\d{12}|\d{13}|\d{14})\b/', $text, $m)) {
                $barcodeVal = $m[1];
            }
            if ($barcodeVal !== '') {
                $codes[] = [
                    'kind' => 'barcode',
                    'value' => $barcodeVal,
                    'x' => 0.12,
                    'y' => 0.68,
                    'w' => 0.76,
                    'h' => 0.2,
                    'show_text' => true,
                ];
            }
        }

        // 중복 value 제거
        $seen = [];
        $out = [];
        foreach ($codes as $c) {
            $key = $c['kind'] . '|' . mb_strtolower($c['value']);
            if (isset($seen[$key])) {
                continue;
            }
            $seen[$key] = true;
            $out[] = $c;
            if (count($out) >= 6) {
                break;
            }
        }
        return $out;
    }

    /**
     * @param array<int, array<string, mixed>> $primary
     * @param array<int, array<string, mixed>> $fallback
     * @return array<int, array{kind:string,value:string,x:float,y:float,w:float,h:float,show_text:bool}>
     */
    private function mergeEditableCodes(array $primary, array $fallback): array
    {
        $out = [];
        $seen = [];
        foreach (array_merge($primary, $fallback) as $row) {
            if (!is_array($row)) {
                continue;
            }
            $kind = strtolower(trim((string) ($row['kind'] ?? '')));
            if ($kind === 'qrcode') {
                $kind = 'qr';
            }
            $value = trim((string) ($row['value'] ?? ''));
            if ($value === '' || !in_array($kind, ['qr', 'barcode'], true)) {
                continue;
            }
            $key = $kind . '|' . mb_strtolower($value);
            if (isset($seen[$key])) {
                continue;
            }
            $seen[$key] = true;
            $x = max(0.0, min(0.95, (float) ($row['x'] ?? ($kind === 'qr' ? 0.72 : 0.12))));
            $y = max(0.0, min(0.95, (float) ($row['y'] ?? ($kind === 'qr' ? 0.62 : 0.68))));
            if ($kind === 'qr') {
                $w = max(0.12, min(1.0 - $x, (float) ($row['w'] ?? 0.22)));
                $h = max(0.12, min(1.0 - $y, (float) ($row['h'] ?? $w)));
                $side = min($w, $h);
                $w = $side;
                $h = $side;
            } else {
                $w = max(0.2, min(1.0 - $x, (float) ($row['w'] ?? 0.76)));
                $h = max(0.08, min(1.0 - $y, (float) ($row['h'] ?? 0.2)));
            }
            $out[] = [
                'kind' => $kind,
                'value' => mb_strlen($value) > 500 ? mb_substr($value, 0, 500) : $value,
                'x' => $x,
                'y' => $y,
                'w' => $w,
                'h' => $h,
                'show_text' => array_key_exists('show_text', $row)
                    ? !empty($row['show_text'])
                    : ($kind === 'barcode'),
            ];
            if (count($out) >= 8) {
                break;
            }
        }
        return $out;
    }

    /** @return array<string, mixed> */
    private function labiBarcodeObject(
        string $id,
        float $x,
        float $y,
        float $w,
        float $h,
        string $value,
        bool $showText,
        int $z
    ): array {
        return [
            'id' => $id,
            'type' => 'barcode',
            'zIndex' => $z,
            'locked' => false,
            'visible' => true,
            'x' => round($x, 2),
            'y' => round($y, 2),
            'width' => round($w, 2),
            'height' => round($h, 2),
            'rotation' => 0,
            'fill' => '#2E2A27',
            'stroke' => 'transparent',
            'strokeWidth' => 0,
            'opacity' => 1,
            'barcodeFormat' => 'CODE_128',
            'barcodeValue' => $value,
            'barcodeShowText' => $showText,
            'fontSize' => 2.2,
            'backgroundTransparent' => true,
        ];
    }

    /** @return array<string, mixed> */
    private function labiQrObject(
        string $id,
        float $x,
        float $y,
        float $size,
        string $value,
        int $z
    ): array {
        $kind = preg_match('#^https?://#i', $value) ? 'url' : 'text';
        return [
            'id' => $id,
            'type' => 'qr',
            'zIndex' => $z,
            'locked' => false,
            'visible' => true,
            'x' => round($x, 2),
            'y' => round($y, 2),
            'width' => round($size, 2),
            'height' => round($size, 2),
            'rotation' => 0,
            'fill' => '#2E2A27',
            'stroke' => 'transparent',
            'strokeWidth' => 0,
            'opacity' => 1,
            'barcodeFormat' => 'QR_CODE',
            'barcodeValue' => $value,
            'barcodeShowText' => false,
            'qrEcc' => 'M',
            'qrKind' => $kind,
            'backgroundTransparent' => true,
        ];
    }
}
