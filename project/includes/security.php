<?php
/**
 * PHP 5.6 호환 보안 유틸 (bootstrap에서 가장 먼저 로드)
 */

if (!function_exists('secure_random_hex')) {
    function secure_random_hex($length = 32)
    {
        $bytes = (int) $length;
        if ($bytes < 1) {
            $bytes = 32;
        }
        if (function_exists('random_bytes')) {
            return bin2hex(random_bytes($bytes));
        }
        if (function_exists('openssl_random_pseudo_bytes')) {
            return bin2hex(openssl_random_pseudo_bytes($bytes));
        }
        $hex = '';
        while (strlen($hex) < ($bytes * 2)) {
            $hex .= sha1(uniqid(mt_rand(), true) . microtime(true));
        }
        return substr($hex, 0, $bytes * 2);
    }
}

if (!function_exists('hash_equals')) {
    function hash_equals($knownString, $userString)
    {
        if (!is_string($knownString) || !is_string($userString)) {
            return false;
        }
        if (strlen($knownString) !== strlen($userString)) {
            return false;
        }
        $res = 0;
        $len = strlen($knownString);
        for ($i = 0; $i < $len; $i++) {
            $res |= ord($knownString[$i]) ^ ord($userString[$i]);
        }
        return $res === 0;
    }
}

if (!function_exists('csrf_token')) {
    function csrf_token()
    {
        if (empty($_SESSION['_csrf'])) {
            $_SESSION['_csrf'] = secure_random_hex(32);
        }
        return $_SESSION['_csrf'];
    }
}

if (!function_exists('csrf_field')) {
    function csrf_field()
    {
        return '<input type="hidden" name="_csrf" value="' . htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8') . '">';
    }
}

if (!function_exists('verify_csrf')) {
    function verify_csrf()
    {
        $token = isset($_POST['_csrf']) ? $_POST['_csrf'] : '';
        return hash_equals(isset($_SESSION['_csrf']) ? $_SESSION['_csrf'] : '', $token);
    }
}
