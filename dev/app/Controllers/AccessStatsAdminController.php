<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Middleware\AuthMiddleware;
use App\Services\AccessStatsService;
use App\Services\AuthService;
use Throwable;

final class AccessStatsAdminController extends BaseController
{
    private AuthService $auth;

    public function __construct()
    {
        $this->auth = new AuthService();
    }

    public function overview(): void
    {
        $this->page('overview', 'stats-overview', '개요', 'admin/stats', '기간 안의 방문, 페이지, 유입, 기기를 한 화면에서 비교합니다.', 'overview');
    }

    public function trend(): void
    {
        $this->page('trend', 'stats-trend', '기간 추이', 'admin/stats/trend', '일·주·월 단위로 페이지뷰, 순방문자, 세션, 회원 수를 비교합니다.', 'trend');
    }

    public function pages(): void
    {
        $this->page('pages', 'stats-pages', '페이지', 'admin/stats/pages', '많이 본 화면, 들어온 첫 페이지, 세션의 마지막 페이지를 확인합니다.', 'pages');
    }

    public function referrers(): void
    {
        $this->page('referrers', 'stats-referrers', '유입 경로', 'admin/stats/referrers', '직접 방문, 검색, 소셜, 외부 링크와 광고 파라미터를 구분합니다.', 'referrers');
    }

    public function visitors(): void
    {
        $this->page('visitors', 'stats-visitors', '방문자', 'admin/stats/visitors', '신규·재방문, 회원·비회원, 자주 오는 방문자를 확인합니다.', 'visitors');
    }

    public function environment(): void
    {
        $this->page('environment', 'stats-environment', '접속 환경', 'admin/stats/environment', '기기, 운영체제, 브라우저, 화면 크기, 언어, 시간대를 나눕니다.', 'environment');
    }

    public function hours(): void
    {
        $this->page('hours', 'stats-hours', '시간대', 'admin/stats/hours', '요일과 시간을 겹쳐 언제 방문이 몰리는지 확인합니다.', 'hours');
    }

    public function live(): void
    {
        $this->page('live', 'stats-live', '실시간', 'admin/stats/live', '최근 5분·15분·1시간의 접속과 방금 열린 페이지를 봅니다.', 'live');
    }

    public function logs(): void
    {
        $this->page('logs', 'stats-logs', '접속 로그', 'admin/stats/logs', '개별 접속 기록을 걸러 보고 CSV로 내려받습니다.', 'logs');
    }

    public function export(): void
    {
        (new AuthMiddleware($this->auth))->handle(true);
        $service = new AccessStatsService();
        $filters = $service->filters($_GET);
        try {
            $rows = $service->logsForExport($filters);
        } catch (Throwable) {
            $rows = [];
        }
        header('Content-Type: text/csv; charset=UTF-8');
        header('Content-Disposition: attachment; filename="access-logs-' . date('Ymd-His') . '.csv"');
        $out = fopen('php://output', 'w');
        if ($out === false) {
            return;
        }
        fwrite($out, "\xEF\xBB\xBF");
        fputcsv($out, ['시간', '영역', '경로', '제목', '상태', '체류(ms)', 'IP', '구분', '기기', 'OS', '브라우저', '유입 호스트', 'utm_source', 'utm_medium', 'utm_campaign', '화면', '언어', '이메일', '이름']);
        foreach ($rows as $row) {
            fputcsv($out, [
                (string) ($row['visited_at'] ?? ''),
                AccessStatsService::sectionLabel((string) ($row['section'] ?? '')),
                (string) ($row['path'] ?? ''),
                (string) ($row['title'] ?? ''),
                (string) ($row['status_code'] ?? ''),
                (string) ($row['dwell_ms'] ?? ''),
                (string) ($row['ip'] ?? ''),
                (string) ($row['actor_role'] ?? ''),
                AccessStatsService::deviceLabel((string) ($row['device'] ?? '')),
                (string) ($row['os_name'] ?? ''),
                (string) ($row['browser_name'] ?? ''),
                (string) ($row['referer_host'] ?? ''),
                (string) ($row['utm_source'] ?? ''),
                (string) ($row['utm_medium'] ?? ''),
                (string) ($row['utm_campaign'] ?? ''),
                (string) ($row['screen'] ?? ''),
                (string) ($row['lang'] ?? ''),
                mask_email_id((string) ($row['email'] ?? '')),
                mask_person_name((string) ($row['name'] ?? '')),
            ]);
        }
        fclose($out);
        exit;
    }

    private function page(string $view, string $menu, string $title, string $action, string $desc, string $method): void
    {
        (new AuthMiddleware($this->auth))->handle(true);
        $service = new AccessStatsService();
        $filters = $service->filters($_GET);
        $error = '';
        $report = [];
        try {
            $report = match ($method) {
                'overview' => $service->overview($filters),
                'trend' => $service->trend($filters),
                'pages' => $service->pages($filters),
                'referrers' => $service->referrers($filters),
                'visitors' => $service->visitors($filters),
                'environment' => $service->environment($filters),
                'hours' => $service->hours($filters),
                'live' => $service->live($filters),
                'logs' => $service->logs($filters, max(1, (int) ($_GET['page'] ?? 1))),
                default => [],
            };
        } catch (Throwable $e) {
            $error = $service->failureMessage($e);
        }
        view('admin/layout', [
            'contentTemplate' => 'admin/stats/' . $view,
            'pageTitle' => '접속통계 › ' . $title . ' — 라벨업 관리자',
            'activeMenu' => $menu,
            'menuGroup' => 'stats',
            'crumbTitle' => '접속통계 › ' . $title,
            'user' => $this->auth->admin(),
            'statsTitle' => $title,
            'statsDesc' => $desc,
            'statsAction' => $action,
            'statsMode' => $method,
            'filters' => $filters,
            'statsError' => $error,
            'report' => $report,
        ]);
    }
}
