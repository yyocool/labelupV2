<?php

declare(strict_types=1);

namespace App\Controllers\Api;

use App\Controllers\BaseController;
use App\Middleware\AuthMiddleware;
use App\Services\AuthService;
use App\Services\UserAddressService;
use RuntimeException;

final class AccountAddressApiController extends BaseController
{
    private AuthService $auth;
    private UserAddressService $addresses;

    public function __construct()
    {
        $this->auth = new AuthService();
        $this->addresses = new UserAddressService();
    }

    public function index(): never
    {
        (new AuthMiddleware($this->auth))->handle();
        $this->jsonSuccess([
            'items' => $this->addresses->list((int) $this->auth->id()),
        ]);
    }

    public function save(): never
    {
        (new AuthMiddleware($this->auth))->handle();
        try {
            $item = $this->addresses->save((int) $this->auth->id(), request_json());
            $this->jsonSuccess(['item' => $item], '배송지를 저장했습니다.');
        } catch (RuntimeException $e) {
            $this->jsonError($e->getMessage(), null, 422);
        }
    }

    public function delete(): never
    {
        (new AuthMiddleware($this->auth))->handle();
        $data = request_json();
        try {
            $this->addresses->delete((int) $this->auth->id(), (int) ($data['id'] ?? 0));
            $this->jsonSuccess(['items' => $this->addresses->list((int) $this->auth->id())], '배송지를 삭제했습니다.');
        } catch (RuntimeException $e) {
            $this->jsonError($e->getMessage(), null, 422);
        }
    }

    public function setDefault(): never
    {
        (new AuthMiddleware($this->auth))->handle();
        $data = request_json();
        try {
            $item = $this->addresses->setDefault((int) $this->auth->id(), (int) ($data['id'] ?? 0));
            $this->jsonSuccess([
                'item' => $item,
                'items' => $this->addresses->list((int) $this->auth->id()),
            ], '기본 배송지로 설정했습니다.');
        } catch (RuntimeException $e) {
            $this->jsonError($e->getMessage(), null, 422);
        }
    }
}
