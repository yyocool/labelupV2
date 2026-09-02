<?php

declare(strict_types=1);

namespace App\Controllers\Api;

use App\Controllers\BaseController;
use App\Services\LegalDocumentService;
use RuntimeException;

final class LegalApiController extends BaseController
{
    private LegalDocumentService $legal;

    public function __construct()
    {
        $this->legal = new LegalDocumentService();
    }

    public function show(string $key): never
    {
        try {
            $this->jsonSuccess($this->legal->get($key));
        } catch (RuntimeException $e) {
            $this->jsonError($e->getMessage(), null, 404);
        }
    }
}
