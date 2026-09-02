<?php

declare(strict_types=1);

namespace App\Controllers\Api;

use App\Controllers\BaseController;
use App\Services\AiExamplePromptService;

final class AiExamplePromptApiController extends BaseController
{
    public function index(): never
    {
        $surface = trim((string) ($_GET['surface'] ?? 'home'));
        try {
            $items = (new AiExamplePromptService())->activeForSurface($surface);
        } catch (\Throwable) {
            $items = [];
        }
        $this->jsonSuccess(['items' => $items]);
    }
}
