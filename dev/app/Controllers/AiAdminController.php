<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Middleware\AuthMiddleware;
use App\Services\AiExamplePromptService;
use App\Services\AiUsageService;
use App\Services\AuthService;

final class AiAdminController extends BaseController
{
    private AuthService $auth;

    public function __construct()
    {
        $this->auth = new AuthService();
    }

    public function examplePrompts(): void
    {
        (new AuthMiddleware($this->auth))->handle(true);
        view('admin/layout', [
            'contentTemplate' => 'admin/ai-example-prompts',
            'pageTitle' => 'AI 관리 › 예시프롬프트 관리 — 라벨업 관리자',
            'activeMenu' => 'ai-example-prompts',
            'menuGroup' => 'ai',
            'crumbTitle' => 'AI 관리 › 예시프롬프트 관리',
            'user' => $this->auth->admin(),
            'items' => (new AiExamplePromptService())->allForAdmin(),
        ]);
    }

    public function usage(): void
    {
        (new AuthMiddleware($this->auth))->handle(true);
        $period = trim((string) ($_GET['period'] ?? '7d'));
        view('admin/layout', [
            'contentTemplate' => 'admin/ai-usage',
            'pageTitle' => 'AI 관리 › 사용량 통계 — 라벨업 관리자',
            'activeMenu' => 'ai-usage',
            'menuGroup' => 'ai',
            'crumbTitle' => 'AI 관리 › 사용량 통계',
            'user' => $this->auth->admin(),
            'stats' => (new AiUsageService())->dashboard($period),
        ]);
    }

    public function tokenLogs(): void
    {
        (new AuthMiddleware($this->auth))->handle(true);
        $filters = [
            'q' => (string) ($_GET['q'] ?? ''),
            'user_id' => (string) ($_GET['user_id'] ?? ''),
            'intent' => (string) ($_GET['intent'] ?? ''),
            'surface' => (string) ($_GET['surface'] ?? ''),
            'status' => (string) ($_GET['status'] ?? ''),
            'from' => (string) ($_GET['from'] ?? ''),
            'to' => (string) ($_GET['to'] ?? ''),
        ];
        $page = max(1, (int) ($_GET['page'] ?? 1));
        view('admin/layout', [
            'contentTemplate' => 'admin/ai-token-logs',
            'pageTitle' => 'AI 관리 › 토큰사용로그 — 라벨업 관리자',
            'activeMenu' => 'ai-token-logs',
            'menuGroup' => 'ai',
            'crumbTitle' => 'AI 관리 › 토큰사용로그',
            'user' => $this->auth->admin(),
            'result' => (new AiUsageService())->searchLogs($filters, $page, 30),
        ]);
    }

    public function tokenLogsExport(): void
    {
        (new AuthMiddleware($this->auth))->handle(true);
        $filters = [
            'q' => (string) ($_GET['q'] ?? ''),
            'user_id' => (string) ($_GET['user_id'] ?? ''),
            'intent' => (string) ($_GET['intent'] ?? ''),
            'surface' => (string) ($_GET['surface'] ?? ''),
            'status' => (string) ($_GET['status'] ?? ''),
            'from' => (string) ($_GET['from'] ?? ''),
            'to' => (string) ($_GET['to'] ?? ''),
        ];
        $result = (new AiUsageService())->searchLogs($filters, 1, 500);
        header('Content-Type: text/csv; charset=UTF-8');
        header('Content-Disposition: attachment; filename="ai-token-logs-' . date('Ymd-His') . '.csv"');
        $out = fopen('php://output', 'w');
        if ($out === false) {
            return;
        }
        fwrite($out, "\xEF\xBB\xBF");
        fputcsv($out, ['시간', '회원', '위치', '유형', '모델', '입력토큰', '출력토큰', '사용토큰', '예상금액(원)', '상태', '오류']);
        foreach ($result['items'] as $row) {
            fputcsv($out, [
                (string) ($row['created_at'] ?? ''),
                (string) ($row['member_label'] ?? ''),
                (string) ($row['surface_label'] ?? ''),
                (string) ($row['intent_label'] ?? ''),
                (string) ($row['model'] ?? ''),
                (int) ($row['prompt_tokens'] ?? 0),
                (int) ($row['completion_tokens'] ?? 0),
                (int) ($row['total_tokens'] ?? 0),
                format_ai_krw($row['cost_krw'] ?? 0),
                (string) ($row['status_label'] ?? ''),
                (string) ($row['error_message'] ?? ''),
            ]);
        }
        fclose($out);
        exit;
    }

    public function memberUsage(): void
    {
        (new AuthMiddleware($this->auth))->handle(true);
        $filters = [
            'q' => (string) ($_GET['q'] ?? ''),
            'intent' => (string) ($_GET['intent'] ?? ''),
            'surface' => (string) ($_GET['surface'] ?? ''),
            'status' => (string) ($_GET['status'] ?? ''),
            'from' => (string) ($_GET['from'] ?? ''),
            'to' => (string) ($_GET['to'] ?? ''),
        ];
        view('admin/layout', [
            'contentTemplate' => 'admin/ai-member-usage',
            'pageTitle' => 'AI 관리 › 회원별 사용 — 라벨업 관리자',
            'activeMenu' => 'ai-member-usage',
            'menuGroup' => 'ai',
            'crumbTitle' => 'AI 관리 › 회원별 사용',
            'user' => $this->auth->admin(),
            'result' => (new AiUsageService())->memberUsage($filters, 100),
        ]);
    }
}
