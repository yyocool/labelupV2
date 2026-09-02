<?php

declare(strict_types=1);

namespace App\Services;

use RuntimeException;

final class OpenAIService
{
    private const CHAT_URL = 'https://api.openai.com/v1/chat/completions';
    private const IMAGE_URL = 'https://api.openai.com/v1/images/generations';

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
        if (!in_array($intent, ['recommend_product', 'generate_clipart', 'chat'], true)) {
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

        return [
            'intent' => $intent,
            'message' => trim((string) ($decoded['message'] ?? '')),
            'product_id' => $productId,
            'search_query' => trim((string) ($decoded['search_query'] ?? '')),
            'clipart_prompt' => trim((string) ($decoded['clipart_prompt'] ?? '')),
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
        $model = trim((string) env('OPENAI_MODEL', 'gpt-4o-mini'));
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

        return $decoded;
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
  "intent": "recommend_product" | "generate_clipart" | "chat",
  "message": "사용자에게 보여줄 친절한 한국어 안내",
  "product_id": null 또는 카탈로그의 숫자 id,
  "search_query": "상품 검색용 짧은 한국어 키워드",
  "clipart_prompt": "이미지 생성용 영어 프롬프트"
}

intent 선택 규칙:
1) recommend_product — 라벨지/스티커 용지·규격·상품 자체를 고르거나 추천할 때.
   - 카탈로그에서 가장 적합한 상품 1개의 id를 product_id에 넣습니다.
   - 확신이 없으면 search_query로 검색 힌트를 넣습니다.
   - message에는 추천 이유(용도·모양·크기)를 2~4문장으로 적습니다.
2) generate_clipart — 라벨 위에 넣을 일러스트·아이콘·로고성 그림·캐릭터·장식 클립아트를 "그려달라/만들어달라"고 할 때.
   - clipart_prompt에 인쇄용 스티커 클립아트에 맞는 영어 프롬프트를 작성합니다.
   - 흰 배경, 중앙 모티브, 텍스트/워터마크 없음, 플랫·선명한 벡터 느낌으로 유도하세요.
3) chat — 일반 질문, 인쇄 팁, 추가 확인이 필요할 때.

원칙:
- 단순 "라벨 추천/만들어줘/주소라벨/바코드라벨"처럼 상품(용지) 선택이면 recommend_product를 우선합니다.
- "고양이 그림 그려줘", "로고 아이콘 만들어줘", "하트 일러스트"처럼 그림 생성이면 generate_clipart입니다.
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
