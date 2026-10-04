<?php

declare(strict_types=1);

namespace App\Models;

use App\Helpers\Database;
use PDO;

abstract class BaseModel
{
    protected PDO $db;

    public function __construct()
    {
        $this->db = Database::connection();
    }

    protected function fetchAll(string $sql, array $params = []): array
    {
        [$sql, $params] = self::expandRepeatedPlaceholders($sql, $params);
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    protected function fetchOne(string $sql, array $params = []): ?array
    {
        [$sql, $params] = self::expandRepeatedPlaceholders($sql, $params);
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    protected function execute(string $sql, array $params = []): bool
    {
        [$sql, $params] = self::expandRepeatedPlaceholders($sql, $params);
        $stmt = $this->db->prepare($sql);
        return $stmt->execute($params);
    }

    protected function lastInsertId(): string
    {
        return (string) $this->db->lastInsertId();
    }

    /**
     * 같은 이름의 named placeholder를 한 쿼리에서 두 번 쓰면
     * PDO가 ATTR_EMULATE_PREPARES=false 환경에서 HY093을 던진다.
     * (예: `created_at = :now, updated_at = :now`)
     * 두 번째 이후 등장을 고유 이름으로 바꾸고 값을 복제한다.
     * 문자열 리터럴·식별자·주석 안의 콜론은 건드리지 않는다.
     *
     * @param array<string, mixed> $params
     * @return array{0: string, 1: array<string, mixed>}
     */
    private static function expandRepeatedPlaceholders(string $sql, array $params): array
    {
        if ($params === [] || array_is_list($params) || !str_contains($sql, ':')) {
            return [$sql, $params];
        }

        $counts = [];
        $added = [];
        $rewritten = preg_replace_callback(
            '~\'(?:[^\'\\\\]|\\\\.|\'\')*\'|"(?:[^"\\\\]|\\\\.|"")*"|`[^`]*`|/\*.*?\*/|(?:--|\#)[^\n]*|:([a-zA-Z_][a-zA-Z0-9_]*)~s',
            static function (array $m) use (&$counts, &$added, $params): string {
                $name = $m[1] ?? '';
                if ($name === '') {
                    return $m[0];
                }
                $counts[$name] = ($counts[$name] ?? 0) + 1;
                if ($counts[$name] === 1) {
                    return $m[0];
                }
                $key = array_key_exists($name, $params) ? $name : ':' . $name;
                if (!array_key_exists($key, $params)) {
                    return $m[0];
                }
                $alias = $name . '_dup' . $counts[$name];
                $added[$alias] = $params[$key];
                return ':' . $alias;
            },
            $sql
        );

        if ($rewritten === null || $added === []) {
            return [$sql, $params];
        }
        return [$rewritten, $params + $added];
    }
}
