<?php

namespace App\Services;

class JwtService
{
    public function sign(array $payload): string
    {
        $header = ['alg' => 'HS256', 'typ' => 'JWT'];
        $secret = $this->secret();
        $segments = [
            $this->base64UrlEncode(json_encode($header, JSON_UNESCAPED_SLASHES)),
            $this->base64UrlEncode(json_encode($payload, JSON_UNESCAPED_SLASHES)),
        ];
        $signature = hash_hmac('sha256', implode('.', $segments), $secret, true);
        $segments[] = $this->base64UrlEncode($signature);
        return implode('.', $segments);
    }

    public function generateToken(int $userId, string $role): string
    {
        $now = time();
        $payload = [
            'sub' => $userId,
            'role' => $role,
            'iat' => $now,
            'exp' => $now + $this->ttlSeconds(),
        ];
        return $this->sign($payload);
    }

    public function verify(?string $token): ?array
    {
        if (!$token) {
            return null;
        }

        $parts = explode('.', $token);
        if (count($parts) !== 3) {
            return null;
        }

        [$encodedHeader, $encodedPayload, $encodedSignature] = $parts;
        $signature = $this->base64UrlDecode($encodedSignature);
        if ($signature === false) {
            return null;
        }

        $expected = hash_hmac('sha256', $encodedHeader.'.'.$encodedPayload, $this->secret(), true);
        if (!hash_equals($expected, $signature)) {
            return null;
        }

        $payloadJson = $this->base64UrlDecode($encodedPayload);
        if ($payloadJson === false) {
            return null;
        }
        $payload = json_decode($payloadJson, true);
        if (!is_array($payload)) {
            return null;
        }

        if (isset($payload['exp']) && time() >= (int) $payload['exp']) {
            return null;
        }

        return $payload;
    }

    private function ttlSeconds(): int
    {
        $raw = trim((string) config('jwt.expires_in', '3600s'));
        if ($raw === '') {
            return 3600;
        }
        if (is_numeric($raw)) {
            return (int) $raw;
        }
        if (preg_match('/^(\d+)\s*([smhd])$/i', $raw, $matches)) {
            $value = (int) $matches[1];
            return match (strtolower($matches[2])) {
                'm' => $value * 60,
                'h' => $value * 3600,
                'd' => $value * 86400,
                default => $value,
            };
        }
        return 3600;
    }

    private function secret(): string
    {
        return (string) config('jwt.secret', 'changeme');
    }

    private function base64UrlEncode(string $data): string
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }

    private function base64UrlDecode(string $data): string|false
    {
        $remainder = strlen($data) % 4;
        if ($remainder) {
            $data .= str_repeat('=', 4 - $remainder);
        }
        return base64_decode(strtr($data, '-_', '+/'));
    }
}
