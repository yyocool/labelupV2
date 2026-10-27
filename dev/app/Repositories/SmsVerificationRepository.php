<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\BaseModel;

final class SmsVerificationRepository extends BaseModel
{
    public function insert(string $phone, string $codeHash, string $expiresAt, ?string $ip): int
    {
        $now = date('Y-m-d H:i:s');
        $this->execute(
            'INSERT INTO sms_verifications
                (phone, purpose, code_hash, attempts, expires_at, request_ip, created_at)
             VALUES
                (:phone, :purpose, :code_hash, 0, :expires_at, :request_ip, :created_at)',
            [
                'phone' => $phone,
                'purpose' => 'signup',
                'code_hash' => $codeHash,
                'expires_at' => $expiresAt,
                'request_ip' => $ip,
                'created_at' => $now,
            ]
        );
        return (int) $this->lastInsertId();
    }

    public function deleteById(int $id): void
    {
        $this->execute('DELETE FROM sms_verifications WHERE id = :id', ['id' => $id]);
    }

    public function invalidateOthers(string $phone, int $keepId): void
    {
        $this->execute(
            'UPDATE sms_verifications
             SET consumed_at = :now
             WHERE phone = :phone AND purpose = :purpose AND consumed_at IS NULL AND id <> :id',
            [
                'now' => date('Y-m-d H:i:s'),
                'phone' => $phone,
                'purpose' => 'signup',
                'id' => $keepId,
            ]
        );
    }

    /** @return array<string, mixed>|null */
    public function latestOpen(string $phone): ?array
    {
        return $this->fetchOne(
            'SELECT * FROM sms_verifications
             WHERE phone = :phone AND purpose = :purpose AND consumed_at IS NULL
             ORDER BY id DESC
             LIMIT 1',
            ['phone' => $phone, 'purpose' => 'signup']
        );
    }

    /** @return array<string, mixed>|null */
    public function findByTokenHash(string $phone, string $tokenHash): ?array
    {
        return $this->fetchOne(
            'SELECT * FROM sms_verifications
             WHERE phone = :phone AND purpose = :purpose AND token_hash = :token_hash
             LIMIT 1',
            ['phone' => $phone, 'purpose' => 'signup', 'token_hash' => $tokenHash]
        );
    }

    public function countSince(string $phone, string $since): int
    {
        $row = $this->fetchOne(
            'SELECT COUNT(*) AS cnt FROM sms_verifications
             WHERE phone = :phone AND purpose = :purpose AND created_at >= :since',
            ['phone' => $phone, 'purpose' => 'signup', 'since' => $since]
        );
        return (int) ($row['cnt'] ?? 0);
    }

    public function countIpSince(string $ip, string $since): int
    {
        $row = $this->fetchOne(
            'SELECT COUNT(*) AS cnt FROM sms_verifications
             WHERE request_ip = :ip AND purpose = :purpose AND created_at >= :since',
            ['ip' => $ip, 'purpose' => 'signup', 'since' => $since]
        );
        return (int) ($row['cnt'] ?? 0);
    }

    public function incrementAttempts(int $id): void
    {
        $this->execute(
            'UPDATE sms_verifications SET attempts = attempts + 1 WHERE id = :id',
            ['id' => $id]
        );
    }

    public function markVerified(int $id, string $tokenHash, string $expiresAt): void
    {
        $this->execute(
            'UPDATE sms_verifications
             SET token_hash = :token_hash, verified_at = :verified_at, expires_at = :expires_at
             WHERE id = :id AND consumed_at IS NULL',
            [
                'token_hash' => $tokenHash,
                'verified_at' => date('Y-m-d H:i:s'),
                'expires_at' => $expiresAt,
                'id' => $id,
            ]
        );
    }

    public function consume(int $id): bool
    {
        $stmt = $this->db->prepare(
            'UPDATE sms_verifications SET consumed_at = :now WHERE id = :id AND consumed_at IS NULL'
        );
        $stmt->execute([
            'now' => date('Y-m-d H:i:s'),
            'id' => $id,
        ]);
        return $stmt->rowCount() === 1;
    }
}
