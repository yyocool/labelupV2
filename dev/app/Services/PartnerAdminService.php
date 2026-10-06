<?php

declare(strict_types=1);

namespace App\Services;

use App\Helpers\Validator;
use App\Repositories\PartnerRepository;
use RuntimeException;

final class PartnerAdminService
{
    private PartnerRepository $partners;

    public function __construct()
    {
        $this->partners = new PartnerRepository();
    }

    /** @return array<int, array<string, mixed>> */
    public function list(string $q = ''): array
    {
        return $this->partners->list(trim($q));
    }

    /**
     * @param array<string, mixed> $input
     * @return array{partner: array<string, mixed>, issued_password: ?string, login_id_issued: bool}
     */
    public function save(array $input): array
    {
        $id = (int) ($input['id'] ?? 0);
        $existing = $id > 0 ? $this->partners->find($id) : null;
        if ($id > 0 && !$existing) {
            throw new RuntimeException('협력사를 찾을 수 없습니다.');
        }

        $company = trim((string) ($input['company_name'] ?? ''));
        if ($company === '' || mb_strlen($company) > 120) {
            throw new RuntimeException('상호는 1~120자로 입력해주세요.');
        }

        $loginRaw = trim((string) ($input['login_id'] ?? ''));
        $loginIssued = false;
        if ($loginRaw === '') {
            if ($existing) {
                throw new RuntimeException('아이디를 입력해주세요.');
            }
            $loginRaw = $this->generateLoginId();
            $loginIssued = true;
        }
        $this->assertLoginId($loginRaw, $id);

        $contact = $this->limit((string) ($input['contact_name'] ?? ''), 80, '담당자명');
        $phone = $this->limit((string) ($input['phone'] ?? ''), 30, '연락처');
        $email = trim((string) ($input['email'] ?? ''));
        if ($email !== '' && !Validator::email($email)) {
            throw new RuntimeException('이메일 형식이 올바르지 않습니다.');
        }
        if (mb_strlen($email) > 190) {
            throw new RuntimeException('이메일은 190자 이내로 입력해주세요.');
        }
        $memo = $this->limit((string) ($input['memo'] ?? ''), 500, '메모');
        $status = (string) ($input['status'] ?? 'active');
        if (!in_array($status, ['active', 'inactive'], true)) {
            throw new RuntimeException('상태를 확인해주세요.');
        }

        $password = (string) ($input['password'] ?? '');
        $issuedPassword = null;
        if ($existing === null) {
            if (trim($password) === '') {
                $password = $this->generatePassword();
            }
            if ($err = Validator::password($password)) {
                throw new RuntimeException($err);
            }
            $issuedPassword = $password;
            $id = $this->partners->create([
                'company_name' => $company,
                'login_id' => $loginRaw,
                'password_hash' => password_hash($password, PASSWORD_DEFAULT),
                'contact_name' => $contact !== '' ? $contact : null,
                'phone' => $phone !== '' ? $phone : null,
                'email' => $email !== '' ? $email : null,
                'memo' => $memo !== '' ? $memo : null,
                'status' => $status,
            ]);
        } else {
            $this->partners->update($id, [
                'company_name' => $company,
                'login_id' => $loginRaw,
                'contact_name' => $contact !== '' ? $contact : null,
                'phone' => $phone !== '' ? $phone : null,
                'email' => $email !== '' ? $email : null,
                'memo' => $memo !== '' ? $memo : null,
                'status' => $status,
            ]);
            if (trim($password) !== '') {
                if ($err = Validator::password($password)) {
                    throw new RuntimeException($err);
                }
                $this->partners->updatePassword($id, password_hash($password, PASSWORD_DEFAULT));
                $issuedPassword = $password;
            }
        }

        $partner = $this->present($this->partners->find($id));
        if ($partner === null) {
            throw new RuntimeException('저장한 협력사를 다시 읽지 못했습니다.');
        }

        return [
            'partner' => $partner,
            'issued_password' => $issuedPassword,
            'login_id_issued' => $loginIssued,
        ];
    }

    /** @return array{partner: array<string, mixed>, issued_password: string} */
    public function resetPassword(int $id): array
    {
        $existing = $this->partners->find($id);
        if (!$existing) {
            throw new RuntimeException('협력사를 찾을 수 없습니다.');
        }
        $password = $this->generatePassword();
        $this->partners->updatePassword($id, password_hash($password, PASSWORD_DEFAULT));
        $partner = $this->present($this->partners->find($id));
        if ($partner === null) {
            throw new RuntimeException('협력사를 찾을 수 없습니다.');
        }

        return [
            'partner' => $partner,
            'issued_password' => $password,
        ];
    }

    public function delete(int $id): void
    {
        if (!$this->partners->find($id)) {
            throw new RuntimeException('협력사를 찾을 수 없습니다.');
        }
        $this->partners->softDelete($id);
    }

    private function assertLoginId(string $loginId, int $exceptId): void
    {
        if (!preg_match('/^[A-Za-z0-9][A-Za-z0-9._-]{2,39}$/', $loginId)) {
            throw new RuntimeException('아이디는 영문 또는 숫자로 시작하고, 3~40자의 영문·숫자·._- 만 사용할 수 있습니다.');
        }
        if ($this->partners->loginIdExists($loginId, $exceptId)) {
            throw new RuntimeException('이미 사용 중인 협력사 아이디입니다.');
        }
    }

    private function limit(string $value, int $max, string $label): string
    {
        $value = trim($value);
        if (mb_strlen($value) > $max) {
            throw new RuntimeException($label . '은 ' . $max . '자 이내로 입력해주세요.');
        }

        return $value;
    }

    private function generateLoginId(): string
    {
        $alphabet = 'abcdefghijkmnpqrstuvwxyz23456789';
        $max = strlen($alphabet) - 1;
        for ($attempt = 0; $attempt < 20; $attempt++) {
            $id = 'pt';
            for ($i = 0; $i < 6; $i++) {
                $id .= $alphabet[random_int(0, $max)];
            }
            if (!$this->partners->loginIdExists($id)) {
                return $id;
            }
        }
        throw new RuntimeException('아이디를 발급하지 못했습니다. 다시 시도해주세요.');
    }

    private function generatePassword(): string
    {
        $letters = 'ABCDEFGHJKLMNPQRSTUVWXYZabcdefghijkmnpqrstuvwxyz';
        $digits = '23456789';
        $all = $letters . $digits;
        $chars = [
            $letters[random_int(0, strlen($letters) - 1)],
            $digits[random_int(0, strlen($digits) - 1)],
        ];
        for ($i = 0; $i < 8; $i++) {
            $chars[] = $all[random_int(0, strlen($all) - 1)];
        }
        for ($i = count($chars) - 1; $i > 0; $i--) {
            $j = random_int(0, $i);
            [$chars[$i], $chars[$j]] = [$chars[$j], $chars[$i]];
        }

        return implode('', $chars);
    }

    /** @param array<string, mixed>|null $row */
    private function present(?array $row): ?array
    {
        if ($row === null) {
            return null;
        }
        unset($row['password_hash']);

        return $row;
    }
}
