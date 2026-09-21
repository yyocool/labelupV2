<?php

declare(strict_types=1);

namespace App\Services;

use RuntimeException;

final class OpenAIService
{
    private const CHAT_URL = 'https://api.openai.com/v1/chat/completions';
    private const IMAGE_URL = 'https://api.openai.com/v1/images/generations';

    /** @var array{model:?string,prompt_tokens:?int,completion_tokens:?int,total_tokens:?int,image_count:int,image_model:?string,image_quality:?string,models:array<int,string>,steps:array<int,array<string,mixed>>}|null */
    private ?array $lastUsage = null;

    private ?string $chatModelOverride = null;

    /** @return array{model:?string,prompt_tokens:?int,completion_tokens:?int,total_tokens:?int,image_count:int,image_model:?string,image_quality:?string,models:array<int,string>,steps:array<int,array<string,mixed>>}|null */
    public function lastUsage(): ?array
    {
        return $this->lastUsage;
    }

    public function setChatModel(?string $model): void
    {
        $model = $model !== null ? trim($model) : '';
        $this->chatModelOverride = $model !== '' ? $model : null;
    }

    public function chat(array $messages): string
    {
        $response = $this->chatRequest(array_merge([
            [
                'role' => 'system',
                'content' => $this->systemPrompt(),
            ],
        ], $messages));

        $content = $response['choices'][0]['message']['content'] ?? null;
        if (!is_string($content) || trim($content) === '') {
            throw new RuntimeException('AI 응답을 받지 못했습니다.');
        }

        return trim($content);
    }

    /**
     * @param array<int, array{role:string, content:mixed}> $messages
     * @param array<int, array{id:int, name:string, sku:string, category:string, shape:string, size:string, material:string}> $catalog
     * @return array{intent:string, message:string, product_id:?int, search_query:string, clipart_prompt:string}
     */
    public function chatLabelAssist(array $messages, array $catalog): array
    {
        $catalogJson = json_encode($catalog, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        if ($catalogJson === false) {
            $catalogJson = '[]';
        }

        $payloadMessages = array_merge([
            [
                'role' => 'system',
                'content' => $this->labelAssistSystemPrompt(),
            ],
            [
                'role' => 'system',
                'content' => "등록된 라벨 상품 카탈로그(JSON):\n{$catalogJson}",
            ],
        ], $messages);

        $response = $this->chatRequest($payloadMessages, [
            'response_format' => ['type' => 'json_object'],
            'temperature' => 0.55,
            'max_tokens' => (int) env('OPENAI_MAX_TOKENS', 1800),
        ]);

        $content = $response['choices'][0]['message']['content'] ?? null;
        if (!is_string($content) || trim($content) === '') {
            throw new RuntimeException('AI 응답을 받지 못했습니다.');
        }

        $decoded = json_decode($content, true);
        if (!is_array($decoded)) {
            return [
                'intent' => 'chat',
                'message' => trim($content),
                'product_id' => null,
                'search_query' => '',
                'clipart_prompt' => '',
            ];
        }

        $intent = (string) ($decoded['intent'] ?? 'chat');
        if (!in_array($intent, ['recommend_product', 'generate_clipart', 'generate_template', 'ask_image_mode', 'chat'], true)) {
            $intent = 'chat';
        }

        $productId = $decoded['product_id'] ?? null;
        if ($productId !== null && $productId !== '') {
            $productId = (int) $productId;
            if ($productId <= 0) {
                $productId = null;
            }
        } else {
            $productId = null;
        }

        $widthMm = isset($decoded['width_mm']) ? (float) $decoded['width_mm'] : 0.0;
        $heightMm = isset($decoded['height_mm']) ? (float) $decoded['height_mm'] : 0.0;

        return [
            'intent' => $intent,
            'message' => trim((string) ($decoded['message'] ?? '')),
            'product_id' => $productId,
            'search_query' => trim((string) ($decoded['search_query'] ?? '')),
            'clipart_prompt' => trim((string) ($decoded['clipart_prompt'] ?? '')),
            'width_mm' => $widthMm > 0 ? $widthMm : 0.0,
            'height_mm' => $heightMm > 0 ? $heightMm : 0.0,
        ];
    }

    /**
     * @param array<int, string> $columns
     * @param array<int, array<int, string>> $sampleRows
     * @return array{title:string, message:string, width_mm:float, height_mm:float, fields:array<int, array{column:string, kind:string}>}
     */
    public function suggestDataLabelLayout(string $sourceName, array $columns, array $sampleRows, string $userHint = ''): array
    {
        $table = '| ' . implode(' | ', $columns) . " |\n| " . implode(' | ', array_fill(0, count($columns), '---')) . " |\n";
        foreach (array_slice($sampleRows, 0, 8) as $row) {
            $table .= '| ' . implode(' | ', $row) . " |\n";
        }
        $hint = trim($userHint);
        $prompt = <<<PROMPT
첨부 표로 라벨 템플릿을 만듭니다. JSON만 출력하세요.
{
  "title": "짧은 한국어 템플릿 이름",
  "message": "사용자에게 보여줄 2~3문장 안내(용도·용지·장당 칸 수 언급)",
  "use_case": "shipping|packing|picking|inventory|hangtag|product|general",
  "paper_no": "LU-3102|LU-3230|LU-3659|LU-3775 중 하나",
  "width_mm": 라벨 가로 mm,
  "height_mm": 라벨 세로 mm,
  "fields": [{"column":"표의 열 이름 그대로","kind":"text|barcode|qr"}]
}
규칙:
- fields는 라벨에 넣을 열만, 최대 7개, column은 아래 표에 있는 이름만. URL·단가·이메일은 제외.
- 이름/수취인/상품명은 text, 바코드·SKU는 barcode.
- 반드시 A4 다칸 용지. 한 장 1칸 금지. width/height는 paper_no에 맞출 것.
용도→용지:
- shipping(배송·수취·주소·택배): LU-3102 (A4 100×50mm 10칸)
- packing(패킹·내품·동봉, 필드 많음): LU-3775 (A4 84×58mm 8칸)
- picking(피킹·SKU·바코드 집품): LU-3230 (A4 70×36mm 14칸)
- inventory(검수·재고·소형 다량): LU-3659 (A4 50×30mm 21칸)
- hangtag(행거·타공): LU-3775
- product(일반 상품): LU-3230 또는 LU-3659
사용자 힌트에 용도가 있으면 그걸 우선하세요.
파일: {$sourceName}
사용자 요청: {$hint}
표:
{$table}
PROMPT;

        $response = $this->chatRequest([
            ['role' => 'system', 'content' => '당신은 라벨업의 데이터 라벨 설계 도우미입니다. JSON만 출력합니다.'],
            ['role' => 'user', 'content' => $prompt],
        ], [
            'response_format' => ['type' => 'json_object'],
            'temperature' => 0.3,
            'max_tokens' => 900,
        ]);

        $content = $response['choices'][0]['message']['content'] ?? '';
        $decoded = is_string($content) ? json_decode($content, true) : null;
        if (!is_array($decoded)) {
            return [
                'title' => '',
                'message' => '',
                'paper_no' => '',
                'width_mm' => 0,
                'height_mm' => 0,
                'fields' => [],
            ];
        }

        $fields = [];
        foreach ($decoded['fields'] ?? [] as $item) {
            if (!is_array($item)) {
                continue;
            }
            $col = trim((string) ($item['column'] ?? ''));
            if ($col === '') {
                continue;
            }
            $kind = strtolower(trim((string) ($item['kind'] ?? 'text')));
            if (!in_array($kind, ['text', 'barcode', 'qr'], true)) {
                $kind = 'text';
            }
            $fields[] = ['column' => $col, 'kind' => $kind];
        }

        return [
            'title' => trim((string) ($decoded['title'] ?? '')),
            'message' => trim((string) ($decoded['message'] ?? '')),
            'use_case' => trim((string) ($decoded['use_case'] ?? $decoded['useCase'] ?? '')),
            'paper_no' => trim((string) ($decoded['paper_no'] ?? $decoded['paperNo'] ?? '')),
            'width_mm' => (float) ($decoded['width_mm'] ?? 0),
            'height_mm' => (float) ($decoded['height_mm'] ?? 0),
            'fields' => $fields,
        ];
    }

    /**
     * @param array<int, array{role:string, content:mixed}> $messages
     * @return array{has_foreign:bool, sample:string, lang:string}
     */
    public function inspectImageLanguage(array $messages): array
    {
        $parts = [];
        for ($i = count($messages) - 1; $i >= 0; $i--) {
            if (($messages[$i]['role'] ?? '') !== 'user' || !is_array($messages[$i]['content'] ?? null)) {
                continue;
            }
            foreach ($messages[$i]['content'] as $part) {
                if (is_array($part) && ($part['type'] ?? '') === 'image_url') {
                    $parts[] = $part;
                }
            }
            if ($parts !== []) {
                break;
            }
        }
        if ($parts === []) {
            return ['has_foreign' => false, 'sample' => '', 'lang' => 'ko'];
        }

        $parts[] = [
            'type' => 'text',
            'text' => '이미지에 보이는 글자를 읽고 JSON만 출력하세요. '
                . '{"has_foreign":true/false,"lang":"ko|en|ja|zh|other","sample":"가장 긴 외국어 본문 한 줄"}. '
                . '규칙: '
                . '1) 한글이 주된 문구(상품명·설명·성분 등)이면 영문 브랜드/로고/약어가 있어도 has_foreign=false, lang=ko. '
                . '2) 의미 있는 외국어 문장·상품 설명이 본문으로 있으면 has_foreign=true (en/ja/zh/other). '
                . '3) 로고성 영문 1~3단어, 단위(ml,g), 바코드 숫자만 있으면 has_foreign=false. '
                . '4) 글자가 거의 없으면 has_foreign=false, lang=ko.',
        ];

        $response = $this->chatRequest([
            ['role' => 'system', 'content' => '당신은 라벨 이미지의 글자 언어를 판별합니다. JSON만 출력합니다.'],
            ['role' => 'user', 'content' => $parts],
        ], [
            'response_format' => ['type' => 'json_object'],
            'temperature' => 0.1,
            'max_tokens' => 220,
        ]);

        $decoded = json_decode((string) ($response['choices'][0]['message']['content'] ?? ''), true);
        if (!is_array($decoded)) {
            return ['has_foreign' => false, 'sample' => '', 'lang' => 'ko'];
        }
        $sample = trim((string) ($decoded['sample'] ?? ''));
        if (mb_strlen($sample) > 28) {
            $sample = mb_substr($sample, 0, 28) . '…';
        }
        return [
            'has_foreign' => !empty($decoded['has_foreign']),
            'sample' => $sample,
            'lang' => trim((string) ($decoded['lang'] ?? '')),
        ];
    }

    /**
     * 첨부 라벨 이미지에서 편집 가능한 텍스트 영역과 배경(그래픽) 프롬프트를 추출한다.
     *
     * @param array<int, array{role:string, content:mixed}> $messages
     * @return array{
     *   title:string,
     *   width_mm:float,
     *   height_mm:float,
     *   background_prompt:string,
     *   texts:array<int, array{
     *     text:string,
     *     x:float,
     *     y:float,
     *     w:float,
     *     h:float,
     *     font_size_mm:float,
     *     bold:bool,
     *     align:string,
     *     color:string
     *   }>
     * }
     */
    public function extractLabelLayout(array $messages, bool $translateToKo = false): array
    {
        $parts = [];
        for ($i = count($messages) - 1; $i >= 0; $i--) {
            if (($messages[$i]['role'] ?? '') !== 'user' || !is_array($messages[$i]['content'] ?? null)) {
                continue;
            }
            foreach ($messages[$i]['content'] as $part) {
                if (is_array($part) && ($part['type'] ?? '') === 'image_url') {
                    $parts[] = $part;
                }
            }
            if ($parts !== []) {
                break;
            }
        }
        if ($parts === []) {
            return [
                'title' => '',
                'width_mm' => 0.0,
                'height_mm' => 0.0,
                'background_prompt' => '',
                'texts' => [],
            ];
        }

        $translateRule = $translateToKo
            ? '4) text 필드는 자연스러운 한국어로 번역한다. 숫자·단위(ml,g,mm)·바코드·영문 브랜드 로고성 1~3단어는 유지.'
            : '4) text 필드는 이미지에 보이는 그대로 옮긴다. 이미 한국어면 그대로.';

        $parts[] = [
            'type' => 'text',
            'text' => "라벨/스티커 이미지를 분석해 JSON만 출력하세요.\n"
                . "스키마: {\"title\":\"짧은 템플릿 제목\",\"width_mm\":숫자또는0,\"height_mm\":숫자또는0,"
                . "\"background_prompt\":\"텍스트를 제외한 배경·장식·도형·패턴·색만 영어로 묘사\","
                . "\"texts\":[{\"text\":\"문자열\",\"x\":0~1,\"y\":0~1,\"w\":0~1,\"h\":0~1,"
                . "\"font_size_mm\":1.5~14,\"bold\":true/false,\"align\":\"left|center|right\",\"color\":\"#RRGGBB\"}]}\n"
                . "규칙:\n"
                . "1) CRITICAL: 사용자가 편집기에서 바꿀 수 있어야 하는 모든 글자·숫자·특수문자·가격·성분·날짜·용량·브랜드명·슬로건은 반드시 texts에 넣는다. "
                . "이미지에 남을 텍스트는 없다. 한 줄(또는 한 블록)씩 분리. "
                . "바코드 막대·QR 패턴·글자 없는 순수 그래픽 마크만 texts에서 제외한다.\n"
                . "2) x,y는 박스 왼쪽 위(라벨 전체 대비 0~1 정규화), w,h는 박스 너비·높이(0~1). "
                . "박스는 글자를 넉넉히 감싸되 서로 심하게 겹치지 않게.\n"
                . "3) background_prompt에는 글자/숫자/문장/가격을 절대 쓰지 말고, 배경색·그라데이션·테두리·장식·일러스트만 묘사.\n"
                . $translateRule . "\n"
                . "5) font_size_mm는 라벨 높이 기준 추정(제목은 크게, 본문은 작게). color는 실제 글자색에 가까운 #hex.\n"
                . "6) 읽을 수 있는 글자가 하나라도 있으면 texts는 비우지 말 것. 정말 그래픽만이면 texts는 [].",
        ];

        $response = $this->chatRequest([
            [
                'role' => 'system',
                'content' => '당신은 라벨 레이아웃 OCR·분해기입니다. 텍스트는 편집 객체로, 배경은 그래픽만 남기도록 JSON만 출력합니다.',
            ],
            ['role' => 'user', 'content' => $parts],
        ], [
            'response_format' => ['type' => 'json_object'],
            'temperature' => 0.15,
            'max_tokens' => 2200,
        ]);

        $decoded = json_decode((string) ($response['choices'][0]['message']['content'] ?? ''), true);
        if (!is_array($decoded)) {
            return [
                'title' => '',
                'width_mm' => 0.0,
                'height_mm' => 0.0,
                'background_prompt' => '',
                'texts' => [],
            ];
        }

        return $this->normalizeEditableLayout($decoded);
    }

    /**
     * 첨부 이미지 없이(또는 OCR 실패 시) 편집 가능 텍스트 + 글자 없는 배경 레이아웃을 설계한다.
     *
     * @param array<int, array{role:string, content:mixed}> $messages
     * @return array{
     *   title:string,
     *   width_mm:float,
     *   height_mm:float,
     *   background_prompt:string,
     *   texts:array<int, array{
     *     text:string,
     *     x:float,
     *     y:float,
     *     w:float,
     *     h:float,
     *     font_size_mm:float,
     *     bold:bool,
     *     align:string,
     *     color:string
     *   }>
     * }
     */
    public function planEditableLabelTemplate(array $messages, bool $translateToKo = false, string $hint = ''): array
    {
        $userHint = trim($hint);
        if ($userHint === '') {
            for ($i = count($messages) - 1; $i >= 0; $i--) {
                if (($messages[$i]['role'] ?? '') !== 'user') {
                    continue;
                }
                $content = $messages[$i]['content'] ?? '';
                if (is_string($content)) {
                    $userHint = trim($content);
                    break;
                }
                if (is_array($content)) {
                    foreach ($content as $part) {
                        if (is_array($part) && ($part['type'] ?? '') === 'text') {
                            $userHint = trim((string) ($part['text'] ?? ''));
                            break 2;
                        }
                    }
                }
            }
        }
        if ($userHint === '') {
            $userHint = '상품 라벨';
        }

        $translateRule = $translateToKo
            ? '텍스트는 자연스러운 한국어. 숫자·단위·영문 브랜드 로고성 1~3단어는 유지.'
            : '사용자가 준 문구를 살리되, 없으면 한국어 샘플 문구를 넣는다.';

        $response = $this->chatRequest([
            [
                'role' => 'system',
                'content' => '당신은 라벨업 템플릿 설계기입니다. '
                    . '사용자가 바꿀 문구는 반드시 텍스트 객체로, 배경 이미지에는 글자를 넣지 않습니다. JSON만 출력합니다.',
            ],
            [
                'role' => 'user',
                'content' => "라벨 템플릿을 설계하세요. JSON만:\n"
                    . "{\"title\":\"짧은 제목\",\"width_mm\":70,\"height_mm\":36,"
                    . "\"background_prompt\":\"글자 없는 배경·장식만 영어 묘사\","
                    . "\"texts\":[{\"text\":\"문구\",\"x\":0~1,\"y\":0~1,\"w\":0~1,\"h\":0~1,"
                    . "\"font_size_mm\":1.5~14,\"bold\":true/false,\"align\":\"left|center|right\",\"color\":\"#RRGGBB\"}]}\n"
                    . "규칙:\n"
                    . "1) CRITICAL: 상품명·가격·용량·날짜·슬로건·설명 등 사용자가 수정할 문구는 전부 texts. "
                    . "이미지(background)에는 글자·숫자·특수문자를 절대 넣지 말 것.\n"
                    . "2) texts는 최소 1개(보통 2~6개). 제목/본문/부가정보를 분리.\n"
                    . "3) background_prompt는 색·패턴·테두리·일러스트만. 워드/숫자 금지.\n"
                    . "4) {$translateRule}\n"
                    . "5) 기본 규격이 없으면 70×36.\n"
                    . "사용자 요청: {$userHint}",
            ],
        ], [
            'response_format' => ['type' => 'json_object'],
            'temperature' => 0.35,
            'max_tokens' => 1400,
        ]);

        $decoded = json_decode((string) ($response['choices'][0]['message']['content'] ?? ''), true);
        if (!is_array($decoded)) {
            return $this->defaultEditableLayout($userHint);
        }
        $layout = $this->normalizeEditableLayout($decoded);
        if ($layout['texts'] === []) {
            return $this->defaultEditableLayout($userHint, $layout);
        }
        return $layout;
    }

    /**
     * @param array<string, mixed> $decoded
     * @return array{
     *   title:string,
     *   width_mm:float,
     *   height_mm:float,
     *   background_prompt:string,
     *   texts:array<int, array{
     *     text:string,
     *     x:float,
     *     y:float,
     *     w:float,
     *     h:float,
     *     font_size_mm:float,
     *     bold:bool,
     *     align:string,
     *     color:string
     *   }>
     * }
     */
    private function normalizeEditableLayout(array $decoded): array
    {
        $texts = [];
        foreach ($decoded['texts'] ?? [] as $row) {
            if (!is_array($row)) {
                continue;
            }
            $text = trim((string) ($row['text'] ?? ''));
            if ($text === '') {
                continue;
            }
            if (mb_strlen($text) > 120) {
                $text = mb_substr($text, 0, 119) . '…';
            }
            $x = max(0.0, min(0.98, (float) ($row['x'] ?? 0)));
            $y = max(0.0, min(0.98, (float) ($row['y'] ?? 0)));
            $w = max(0.04, min(1.0 - $x, (float) ($row['w'] ?? 0.3)));
            $h = max(0.03, min(1.0 - $y, (float) ($row['h'] ?? 0.1)));
            $font = (float) ($row['font_size_mm'] ?? ($h * 40));
            if ($font < 1.5) {
                $font = max(1.5, $h * 28);
            }
            $font = max(1.5, min(14.0, $font));
            $align = strtolower(trim((string) ($row['align'] ?? 'left')));
            if (!in_array($align, ['left', 'center', 'right'], true)) {
                $align = 'left';
            }
            $color = trim((string) ($row['color'] ?? '#2E2A27'));
            if (!preg_match('/^#[0-9A-Fa-f]{6}$/', $color)) {
                $color = '#2E2A27';
            }
            $texts[] = [
                'text' => $text,
                'x' => $x,
                'y' => $y,
                'w' => $w,
                'h' => $h,
                'font_size_mm' => $font,
                'bold' => !empty($row['bold']),
                'align' => $align,
                'color' => $color,
            ];
            if (count($texts) >= 40) {
                break;
            }
        }

        $bg = trim((string) ($decoded['background_prompt'] ?? ''));
        if (mb_strlen($bg) > 700) {
            $bg = mb_substr($bg, 0, 700);
        }

        return [
            'title' => trim((string) ($decoded['title'] ?? '')),
            'width_mm' => (float) ($decoded['width_mm'] ?? 0),
            'height_mm' => (float) ($decoded['height_mm'] ?? 0),
            'background_prompt' => $bg,
            'texts' => $texts,
        ];
    }

    /**
     * @param array{title?:string,width_mm?:float,height_mm?:float,background_prompt?:string}|null $base
     * @return array{
     *   title:string,
     *   width_mm:float,
     *   height_mm:float,
     *   background_prompt:string,
     *   texts:array<int, array{
     *     text:string,
     *     x:float,
     *     y:float,
     *     w:float,
     *     h:float,
     *     font_size_mm:float,
     *     bold:bool,
     *     align:string,
     *     color:string
     *   }>
     * }
     */
    private function defaultEditableLayout(string $hint, ?array $base = null): array
    {
        $title = trim((string) ($base['title'] ?? ''));
        if ($title === '') {
            $title = '라비가 만든 라벨 템플릿';
        }
        $sample = trim(mb_substr($hint !== '' ? $hint : '상품명', 0, 24));
        if ($sample === '') {
            $sample = '상품명';
        }
        $bg = trim((string) ($base['background_prompt'] ?? ''));
        if ($bg === '') {
            $bg = 'Soft cream full-bleed label background with subtle burgundy border frame and gentle decorative corner ornaments, no letters no numbers no words';
        }

        return [
            'title' => $title,
            'width_mm' => (float) ($base['width_mm'] ?? 70),
            'height_mm' => (float) ($base['height_mm'] ?? 36),
            'background_prompt' => $bg,
            'texts' => [
                [
                    'text' => $sample,
                    'x' => 0.08,
                    'y' => 0.28,
                    'w' => 0.84,
                    'h' => 0.22,
                    'font_size_mm' => 5.5,
                    'bold' => true,
                    'align' => 'center',
                    'color' => '#7B2840',
                ],
                [
                    'text' => '내용을 수정하세요',
                    'x' => 0.1,
                    'y' => 0.55,
                    'w' => 0.8,
                    'h' => 0.16,
                    'font_size_mm' => 3.2,
                    'bold' => false,
                    'align' => 'center',
                    'color' => '#2E2A27',
                ],
            ],
        ];
    }

    /**
     * @param array{source_name:string, source_kind:string, columns:array<int,string>, rows:array<int,array<int,string>>, summary?:string} $sheet
     * @return array{source_name:string, source_kind:string, columns:array<int,string>, rows:array<int,array<int,string>>, summary:string}
     */
    public function translateLabelSheet(array $sheet): array
    {
        $uniques = [];
        foreach ($sheet['columns'] ?? [] as $col) {
            $col = trim((string) $col);
            if ($col !== '') {
                $uniques[$col] = true;
            }
        }
        foreach ($sheet['rows'] ?? [] as $row) {
            if (!is_array($row)) {
                continue;
            }
            foreach ($row as $cell) {
                $cell = trim((string) $cell);
                if ($cell !== '' && !isset($uniques[$cell])) {
                    $uniques[$cell] = true;
                }
                if (count($uniques) >= 180) {
                    break 2;
                }
            }
        }
        $list = array_keys($uniques);
        if ($list === []) {
            return $sheet;
        }

        $json = json_encode($list, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        $response = $this->chatRequest([
            ['role' => 'system', 'content' => '당신은 라벨 데이터 번역기입니다. JSON만 출력합니다.'],
            ['role' => 'user', 'content' => "아래 문자열을 자연스러운 한국어로 번역하세요. JSON만 출력: {\"map\":{\"원문\":\"번역\"}}.\n"
                . "숫자, 단위(g,ml,mm), SKU, 바코드, URL, 이메일, 모델번호는 그대로 둡니다. 이미 한국어면 그대로 둡니다.\n"
                . $json],
        ], [
            'response_format' => ['type' => 'json_object'],
            'temperature' => 0.2,
            'max_tokens' => 2200,
        ]);

        $decoded = json_decode((string) ($response['choices'][0]['message']['content'] ?? ''), true);
        $map = is_array($decoded['map'] ?? null) ? $decoded['map'] : [];
        $apply = static function (string $value) use ($map): string {
            $key = trim($value);
            if ($key === '' || !isset($map[$key])) {
                return $value;
            }
            $out = trim((string) $map[$key]);
            return $out !== '' ? $out : $value;
        };

        $sheet['columns'] = array_map($apply, $sheet['columns'] ?? []);
        $sheet['rows'] = array_map(static function ($row) use ($apply) {
            if (!is_array($row)) {
                return $row;
            }
            return array_map(static fn ($cell) => $apply((string) $cell), $row);
        }, $sheet['rows'] ?? []);

        return $this->refreshSheetSummary($sheet);
    }

    /**
     * @param array{source_name:string, source_kind:string, columns:array<int,string>, rows:array<int,array<int,string>>, summary?:string} $sheet
     * @return array{source_name:string, source_kind:string, columns:array<int,string>, rows:array<int,array<int,string>>, summary:string}
     */
    private function refreshSheetSummary(array $sheet): array
    {
        $cols = $sheet['columns'] ?? [];
        $rows = $sheet['rows'] ?? [];
        $preview = array_slice($rows, 0, 6);
        $lines = ['| ' . implode(' | ', $cols) . ' |', '| ' . implode(' | ', array_fill(0, max(1, count($cols)), '---')) . ' |'];
        foreach ($preview as $row) {
            $lines[] = '| ' . implode(' | ', is_array($row) ? $row : []) . ' |';
        }
        $sheet['summary'] = sprintf(
            "파일: %s\n열 %d개 · 행 %d개\n\n%s",
            (string) ($sheet['source_name'] ?? ''),
            count($cols),
            count($rows),
            implode("\n", $lines)
        );
        return $sheet;
    }

    /**
     * @return array{url:string, prompt:string, title:string}
     */
    public function generateClipart(string $prompt): array
    {
        $apiKey = $this->apiKey();
        $model = trim((string) env('OPENAI_IMAGE_MODEL', 'gpt-image-1'));
        if ($model === '') {
            $model = 'gpt-image-1';
        }

        $cleanPrompt = trim($prompt);
        if ($cleanPrompt === '') {
            throw new RuntimeException('이미지 생성 프롬프트가 비어 있습니다.');
        }
        if (mb_strlen($cleanPrompt) > 900) {
            $cleanPrompt = mb_substr($cleanPrompt, 0, 900);
        }

        $quality = trim((string) env('OPENAI_IMAGE_QUALITY', 'medium')) ?: 'medium';
        try {
            $response = $this->request($apiKey, self::IMAGE_URL, $this->imagePayload($model, $cleanPrompt), 180);
        } catch (RuntimeException $e) {
            $msg = $e->getMessage();
            if (str_contains($msg, 'response_format')) {
                $payload = $this->imagePayload($model, $cleanPrompt);
                unset($payload['response_format'], $payload['style']);
                $response = $this->request($apiKey, self::IMAGE_URL, $payload, 180);
            } elseif (
                (str_contains($msg, 'does not exist') || str_contains($msg, 'not have access') || str_contains($msg, 'invalid_model'))
                && $model !== 'gpt-image-1'
            ) {
                $model = 'gpt-image-1';
                $response = $this->request($apiKey, self::IMAGE_URL, $this->imagePayload($model, $cleanPrompt), 180);
            } else {
                throw $e;
            }
        }

        $this->recordImageUsage($model, $quality, 1);

        $item = $response['data'][0] ?? null;
        if (!is_array($item)) {
            throw new RuntimeException('이미지 생성 결과를 받지 못했습니다.');
        }

        $b64 = (string) ($item['b64_json'] ?? '');
        $remoteUrl = (string) ($item['url'] ?? '');

        if ($b64 !== '') {
            $url = $this->storeClipartFromBase64($b64);
        } elseif ($remoteUrl !== '') {
            $url = $this->storeClipartFromUrl($remoteUrl);
        } else {
            throw new RuntimeException('생성된 이미지 데이터가 없습니다.');
        }

        return [
            'url' => $url,
            'prompt' => $cleanPrompt,
            'title' => '라비가 그린 클립아트',
        ];
    }

    /** @return array<string, mixed> */
    private function imagePayload(string $model, string $prompt): array
    {
        $isGptImage = str_starts_with($model, 'gpt-image');
        $payload = [
            'model' => $model,
            'prompt' => $prompt,
            'n' => 1,
            'size' => '1024x1024',
        ];

        // dall-e-2/3 only — gpt-image-* always returns b64 and rejects response_format
        if (!$isGptImage) {
            $payload['response_format'] = 'b64_json';
        }

        if (str_starts_with($model, 'dall-e-3')) {
            $payload['quality'] = 'standard';
            $payload['style'] = 'vivid';
        } elseif ($isGptImage) {
            $payload['quality'] = trim((string) env('OPENAI_IMAGE_QUALITY', 'medium')) ?: 'medium';
        }

        return $payload;
    }

    /** @param array<string, mixed> $overrides */
    private function chatRequest(array $messages, array $overrides = []): array
    {
        $apiKey = $this->apiKey();
        $model = $this->chatModelOverride
            ?? trim((string) env('OPENAI_MODEL', 'gpt-4o-mini'));
        if ($model === '') {
            $model = 'gpt-4o-mini';
        }

        $payload = array_merge([
            'model' => $model,
            'messages' => $messages,
            'max_tokens' => (int) env('OPENAI_MAX_TOKENS', 1800),
            'temperature' => 0.7,
        ], $overrides);

        try {
            return $this->request($apiKey, self::CHAT_URL, $payload, 120);
        } catch (RuntimeException $e) {
            if (isset($payload['response_format']) && str_contains($e->getMessage(), 'response_format')) {
                unset($payload['response_format']);
                return $this->request($apiKey, self::CHAT_URL, $payload, 120);
            }
            throw $e;
        }
    }

    private function apiKey(): string
    {
        $apiKey = trim((string) env('OPENAI_API_KEY', ''));
        if ($apiKey === '') {
            throw new RuntimeException('OpenAI API 키가 설정되지 않았습니다. 서버 .env에 OPENAI_API_KEY를 추가해 주세요.');
        }

        return $apiKey;
    }

    /** @param array<string, mixed> $payload */
    private function request(string $apiKey, string $url, array $payload, int $timeout = 120): array
    {
        $body = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        if ($body === false) {
            throw new RuntimeException('요청 데이터를 만들 수 없습니다.');
        }

        if (function_exists('curl_init')) {
            $ch = curl_init($url);
            curl_setopt_array($ch, [
                CURLOPT_POST => true,
                CURLOPT_HTTPHEADER => [
                    'Content-Type: application/json',
                    'Authorization: Bearer ' . $apiKey,
                ],
                CURLOPT_POSTFIELDS => $body,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT => $timeout,
            ]);
            $raw = curl_exec($ch);
            $status = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $error = curl_error($ch);
            curl_close($ch);

            if ($raw === false) {
                throw new RuntimeException('OpenAI API 연결 실패: ' . ($error ?: 'unknown'));
            }
        } else {
            $context = stream_context_create([
                'http' => [
                    'method' => 'POST',
                    'header' => "Content-Type: application/json\r\nAuthorization: Bearer {$apiKey}\r\n",
                    'content' => $body,
                    'timeout' => $timeout,
                    'ignore_errors' => true,
                ],
            ]);
            $raw = file_get_contents($url, false, $context);
            $status = 0;
            if (isset($http_response_header[0]) && preg_match('#\s(\d{3})\s#', $http_response_header[0], $m)) {
                $status = (int) $m[1];
            }
            if ($raw === false) {
                throw new RuntimeException('OpenAI API 연결에 실패했습니다.');
            }
        }

        $decoded = json_decode($raw, true);
        if (!is_array($decoded)) {
            throw new RuntimeException('OpenAI API 응답을 해석할 수 없습니다.');
        }

        if ($status >= 400 || isset($decoded['error'])) {
            $message = is_array($decoded['error'] ?? null)
                ? (string) ($decoded['error']['message'] ?? 'OpenAI API 오류')
                : 'OpenAI API 오류';
            throw new RuntimeException($message);
        }

        $isImage = str_contains($url, '/images/');
        if (!$isImage) {
            $usage = is_array($decoded['usage'] ?? null) ? $decoded['usage'] : [];
            $modelName = isset($decoded['model'])
                ? (string) $decoded['model']
                : (isset($payload['model']) ? (string) $payload['model'] : null);
            $this->recordChatUsage(
                $modelName,
                isset($usage['prompt_tokens']) ? (int) $usage['prompt_tokens'] : 0,
                isset($usage['completion_tokens']) ? (int) $usage['completion_tokens'] : 0,
                isset($usage['total_tokens']) ? (int) $usage['total_tokens'] : null
            );
        }

        return $decoded;
    }

    private function ensureUsageBag(): void
    {
        if ($this->lastUsage !== null) {
            return;
        }
        $this->lastUsage = [
            'model' => null,
            'prompt_tokens' => 0,
            'completion_tokens' => 0,
            'total_tokens' => 0,
            'image_count' => 0,
            'image_model' => null,
            'image_quality' => null,
            'models' => [],
            'steps' => [],
        ];
    }

    private function recordChatUsage(?string $model, int $promptTokens, int $completionTokens, ?int $totalTokens): void
    {
        $this->ensureUsageBag();
        $total = $totalTokens ?? ($promptTokens + $completionTokens);
        $this->lastUsage['prompt_tokens'] = (int) $this->lastUsage['prompt_tokens'] + $promptTokens;
        $this->lastUsage['completion_tokens'] = (int) $this->lastUsage['completion_tokens'] + $completionTokens;
        $this->lastUsage['total_tokens'] = (int) $this->lastUsage['total_tokens'] + $total;
        if ($model) {
            $this->lastUsage['model'] = $model;
            if (!in_array($model, $this->lastUsage['models'], true)) {
                $this->lastUsage['models'][] = $model;
            }
        }
        $this->lastUsage['steps'][] = [
            'kind' => 'chat',
            'model' => $model,
            'prompt_tokens' => $promptTokens,
            'completion_tokens' => $completionTokens,
            'total_tokens' => $total,
        ];
    }

    private function recordImageUsage(string $model, string $quality, int $count = 1): void
    {
        $this->ensureUsageBag();
        $count = max(1, $count);
        $this->lastUsage['image_count'] = (int) $this->lastUsage['image_count'] + $count;
        $this->lastUsage['image_model'] = $model;
        $this->lastUsage['image_quality'] = $quality;
        if ($model !== '' && !in_array($model, $this->lastUsage['models'], true)) {
            $this->lastUsage['models'][] = $model;
        }
        if (empty($this->lastUsage['model'])) {
            $this->lastUsage['model'] = $model;
        }
        $this->lastUsage['steps'][] = [
            'kind' => 'image',
            'model' => $model,
            'quality' => $quality,
            'image_count' => $count,
        ];
    }

    private function storeClipartFromBase64(string $b64): string
    {
        $bin = base64_decode($b64, true);
        if ($bin === false || $bin === '') {
            throw new RuntimeException('이미지 디코딩에 실패했습니다.');
        }

        return $this->writeClipartFile($bin);
    }

    private function storeClipartFromUrl(string $remoteUrl): string
    {
        $bin = false;
        if (function_exists('curl_init')) {
            $ch = curl_init($remoteUrl);
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_TIMEOUT => 60,
            ]);
            $bin = curl_exec($ch);
            curl_close($ch);
        } else {
            $bin = @file_get_contents($remoteUrl);
        }

        if (!is_string($bin) || $bin === '') {
            // fallback: return remote URL directly
            return $remoteUrl;
        }

        return $this->writeClipartFile($bin);
    }

    private function writeClipartFile(string $bin): string
    {
        $dir = public_path('assets/ai-clipart');
        if (!is_dir($dir) && !mkdir($dir, 0777, true) && !is_dir($dir)) {
            // fallback under storage (always writable on remote)
            $dir = dirname(__DIR__, 2) . '/storage/ai-clipart';
            if (!is_dir($dir) && !mkdir($dir, 0777, true) && !is_dir($dir)) {
                throw new RuntimeException('클립아트 저장 경로를 만들 수 없습니다.');
            }
        }

        @chmod($dir, 0777);

        $name = 'clip_' . date('Ymd_His') . '_' . bin2hex(random_bytes(4)) . '.png';
        $full = $dir . DIRECTORY_SEPARATOR . $name;
        if (@file_put_contents($full, $bin) === false) {
            throw new RuntimeException('클립아트 파일 저장에 실패했습니다. 저장 폴더 권한을 확인해 주세요.');
        }
        @chmod($full, 0666);

        // public/www assets path (document root)
        $norm = str_replace('\\', '/', $full);
        if (str_contains($norm, '/www/assets/ai-clipart/') || str_contains($norm, '/public/assets/ai-clipart/')) {
            return url('assets/ai-clipart/' . $name);
        }

        // storage fallback: copy into document-root assets when possible
        $publicDir = public_path('assets/ai-clipart');
        if (!is_dir($publicDir)) {
            @mkdir($publicDir, 0777, true);
        }
        $publicFull = $publicDir . DIRECTORY_SEPARATOR . $name;
        if (@copy($full, $publicFull)) {
            @chmod($publicFull, 0666);
            return url('assets/ai-clipart/' . $name);
        }

        return url('assets/ai-clipart/' . $name);
    }

    private function labelAssistSystemPrompt(): string
    {
        return <<<'PROMPT'
당신은 라벨업(LabelUp)의 AI 라벨 도우미 "라비"입니다. 항상 한국어로 답합니다.

반드시 아래 JSON 객체만 출력하세요 (설명 문장·마크다운 금지):
{
  "intent": "recommend_product" | "generate_clipart" | "generate_template" | "ask_image_mode" | "chat",
  "message": "사용자에게 보여줄 친절한 한국어 안내",
  "product_id": null 또는 카탈로그의 숫자 id,
  "search_query": "상품 검색용 짧은 한국어 키워드",
  "clipart_prompt": "이미지 생성용 영어 프롬프트",
  "width_mm": 라벨 가로 mm 숫자 또는 0,
  "height_mm": 라벨 세로 mm 숫자 또는 0
}

intent 선택 규칙:
1) recommend_product — 라벨지/스티커 용지·규격·상품 자체를 고르거나 추천할 때.
   - 카탈로그에서 가장 적합한 상품 1개의 id를 product_id에 넣습니다.
   - 확신이 없으면 search_query로 검색 힌트를 넣습니다.
   - message에는 추천 이유(용도·모양·크기)를 2~4문장으로 적습니다.
2) generate_clipart — 라벨 위에 넣을 일러스트·아이콘·로고성 그림·캐릭터·장식 클립아트를 "그려달라"고 할 때.
   - clipart_prompt에 인쇄용 스티커 클립아트에 맞는 영어 프롬프트를 작성합니다.
   - 흰 배경, 중앙 모티브, 텍스트/워터마크 없음, 플랫·선명한 벡터 느낌으로 유도하세요.
   - 첨부 이미지가 있으면 그 분위기·모티프를 반영하되 장식이 되는 클립아트로 재해석합니다.
3) generate_template — 완성된 라벨 디자인/템플릿을 만들어 편집기에서 쓰려 할 때.
   - clipart_prompt에는 글자·숫자·특수문자가 없는 배경/장식만 영어로 묘사합니다.
   - 절대 이미지 안에 문구·가격·날짜·성분 등 텍스트를 그리지 마세요. (텍스트는 서버가 별도 텍스트 오브젝트로 만듭니다.)
   - 캔버스를 가장자리까지 채우고(full-bleed), 목업·책상·찢어진 종이·여백 배경은 넣지 마세요.
   - 첨부 이미지의 구도·색·장식 분위기를 살립니다.
   - width_mm/height_mm에 적당한 라벨 규격(없으면 70×36)을 넣습니다.
4) ask_image_mode — 첨부 이미지가 있는데 클립아트인지 템플릿인지 분명하지 않을 때.
   - 이미지를 생성하지 않습니다.
   - message에서 클립아트 그리기 / 템플릿 만들기 중 고르라고 짧게 안내합니다.
5) chat — 일반 질문, 인쇄 팁, 추가 확인이 필요할 때.

원칙:
- 단순 "라벨 추천/주소라벨/바코드라벨"처럼 상품(용지) 선택이면 recommend_product를 우선합니다.
- "고양이 그림 그려줘", "로고 아이콘 만들어줘"처럼 그림만 생성이면 generate_clipart입니다.
- "템플릿 만들어줘", "이 사진으로 라벨 디자인 만들어줘"면 generate_template입니다.
- 이미지만 보냈거나 "이거 참고해서"처럼 목적이 모호하면 ask_image_mode입니다. 추측으로 바로 그리지 마세요.
- product_id는 카탈로그에 있는 id만 사용합니다.
- message는 불필요하게 길지 않게 핵심만 전달합니다.
PROMPT;
    }

    private function systemPrompt(): string
    {
        return <<<'PROMPT'
당신은 라벨업(LabelUp)의 AI 라벨 디자인 어시스턴트 "라비"입니다.
사용자가 원하는 라벨·스티커·태그 디자인을 한국어로 친절하게 도와주세요.

역할:
- 라벨 용도, 규격, 재질, 색감, 넣을 텍스트·로고·바코드 등을 질문하고 구체적인 디자인 방향을 제안합니다.
- 첨부 이미지가 있으면 참고하여 스타일·색상·구성을 분석해 반영합니다.
- 실무에 바로 쓸 수 있도록 레이아웃, 폰트 톤, 여백, 인쇄 시 주의사항을 짧게 정리합니다.

원칙:
- 항상 한국어로 답변합니다.
- 불필요하게 길지 않게, 핵심 위주로 답합니다.
- 확실하지 않은 사양은 가정을 밝히고 확인 질문을 덧붙입니다.
- 라벨업 서비스 맥락(쇼핑몰, 템플릿, 출력)에 맞게 안내합니다.
PROMPT;
    }
}
