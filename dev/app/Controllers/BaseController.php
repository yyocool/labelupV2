<?php

declare(strict_types=1);

namespace App\Controllers;

abstract class BaseController
{
    protected function render(string $template, array $data = []): void
    {
        view($template, $data);
    }

    protected function jsonSuccess(mixed $data = null, string $message = '', int $status = 200): never
    {
        \App\Helpers\ApiResponse::success($data, $message, $status);
    }

    protected function jsonError(string $message, mixed $data = null, int $status = 400): never
    {
        \App\Helpers\ApiResponse::error($message, $data, $status);
    }
}
