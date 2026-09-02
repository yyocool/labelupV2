<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Services\SeoService;

final class SeoPublicController extends BaseController
{
    private SeoService $seo;

    public function __construct()
    {
        $this->seo = new SeoService();
    }

    public function robots(): never
    {
        $this->plain($this->seo->robotsTxt(), 'text/plain; charset=UTF-8');
    }

    public function sitemap(): never
    {
        $this->plain($this->seo->sitemapXml(), 'application/xml; charset=UTF-8');
    }

    public function adsTxt(): never
    {
        $body = $this->seo->adsTxt();
        if (trim($body) === '') {
            http_response_code(404);
            $this->plain("Not found\n", 'text/plain; charset=UTF-8', 404);
        }
        $this->plain($body, 'text/plain; charset=UTF-8');
    }

    public function appAdsTxt(): never
    {
        $body = $this->seo->appAdsTxt();
        if (trim($body) === '') {
            http_response_code(404);
            $this->plain("Not found\n", 'text/plain; charset=UTF-8', 404);
        }
        $this->plain($body, 'text/plain; charset=UTF-8');
    }

    public function tryServeFile(string $path): bool
    {
        $name = ltrim($path, '/');
        if ($name === '' || str_contains($name, '/')) {
            return false;
        }
        $file = $this->seo->fileByName($name);
        if (!$file) {
            return false;
        }
        $kind = (string) ($file['file_kind'] ?? 'html');
        $mime = $kind === 'txt' ? 'text/plain; charset=UTF-8' : 'text/html; charset=UTF-8';
        $this->plain((string) ($file['content'] ?? ''), $mime);
    }

    private function plain(string $body, string $type, int $status = 200): never
    {
        http_response_code($status);
        header('Content-Type: ' . $type);
        header('X-Robots-Tag: noindex');
        echo $body;
        exit;
    }
}
