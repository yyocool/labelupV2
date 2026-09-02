<?php

declare(strict_types=1);

namespace App\Services;

final class MailService
{
    public function sendPasswordReset(string $to, string $name, string $resetUrl): bool
    {
        $subject = '[라벨업] 비밀번호 재설정 안내';
        $body = implode("\n", [
            '안녕하세요, ' . $name . '님.',
            '',
            '라벨업 비밀번호 재설정을 요청하셨습니다.',
            '아래 링크에서 30분 이내에 새 비밀번호를 설정해주세요.',
            '',
            $resetUrl,
            '',
            '본인이 요청하지 않았다면 이 메일을 무시해주세요.',
        ]);

        $mail = app_config('mail', []);
        $from = is_array($mail) ? ($mail['from'] ?? 'noreply@labelup.kr') : 'noreply@labelup.kr';
        $headers = 'From: ' . $from . "\r\n" . 'Content-Type: text/plain; charset=UTF-8';

        $sent = @mail($to, '=?UTF-8?B?' . base64_encode($subject) . '?=', $body, $headers);

        if (!$sent) {
            $logDir = storage_path('logs');
            if (!is_dir($logDir)) {
                @mkdir($logDir, 0775, true);
            }
            @file_put_contents(
                $logDir . '/mail.log',
                date('Y-m-d H:i:s') . " [password-reset] {$to}\n{$resetUrl}\n\n",
                FILE_APPEND
            );
        }

        return $sent;
    }
}
