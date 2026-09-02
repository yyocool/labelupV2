<?php

declare(strict_types=1);

namespace App\Helpers;

final class Validator
{
    public static function email(string $email): bool
    {
        return (bool) filter_var($email, FILTER_VALIDATE_EMAIL);
    }

    public static function password(string $password): ?string
    {
        if (strlen($password) < 8) {
            return '비밀번호는 8자 이상이어야 합니다.';
        }
        if (!preg_match('/[A-Za-z]/', $password) || !preg_match('/[0-9]/', $password)) {
            return '비밀번호는 영문과 숫자를 포함해야 합니다.';
        }
        return null;
    }

    public static function name(string $name): ?string
    {
        $name = trim($name);
        if ($name === '' || mb_strlen($name) < 2) {
            return '이름은 2자 이상 입력해주세요.';
        }
        return null;
    }
}
