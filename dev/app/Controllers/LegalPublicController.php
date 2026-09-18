<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Services\CompanyInfoService;
use App\Services\LegalDocumentService;
use RuntimeException;
use Throwable;

final class LegalPublicController extends BaseController
{
    private LegalDocumentService $legal;

    public function __construct()
    {
        $this->legal = new LegalDocumentService();
    }

    public function terms(): void
    {
        $this->show('terms');
    }

    public function privacy(): void
    {
        $this->show('privacy');
    }

    private function show(string $key): void
    {
        try {
            $doc = $this->legal->get($key);
        } catch (RuntimeException|Throwable) {
            http_response_code(404);
            view('errors/404');
            return;
        }

        $this->render('legal/document', [
            'pageTitle' => (string) ($doc['title'] ?? '약관') . ' — 라벨업',
            'seoPage' => $key === 'privacy' ? 'privacy' : 'terms',
            'doc' => $doc,
            'company' => (new CompanyInfoService())->all(),
            'year' => (int) date('Y'),
        ]);
    }
}
