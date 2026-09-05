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

        // public assets path
        if (str_contains(str_replace('\\', '/', $full), '/public/assets/ai-clipart/')) {
            return url('assets/ai-clipart/' . $name);
        }

        // storage fallback: expose via temporary data URL is heavy; copy to public if possible
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
   - clipart_prompt에 라벨 전체를 채우는 인쇄용 아트워크 영어 프롬프트를 작성합니다.
   - 캔버스를 가장자리까지 채우고(full-bleed), 목업·책상·찢어진 종이·여백 배경은 넣지 마세요.
   - 첨부 이미지의 구도·색·텍스트 분위기를 살립니다.
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
