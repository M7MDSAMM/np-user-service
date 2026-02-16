<?php

namespace App\Infrastructure\Auth;

use App\Domain\Admin\Admin;
use App\Domain\Auth\InvalidTokenException;
use App\Domain\Auth\JwtTokenServiceInterface;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Illuminate\Support\Str;

class Rs256JwtTokenService implements JwtTokenServiceInterface
{
    private string $privateKeyPath;
    private string $publicKeyPath;
    private string $issuer;
    private string $audience;
    private int $ttl;

    public function __construct()
    {
        $this->privateKeyPath = config('jwt.keys.private');
        $this->publicKeyPath  = config('jwt.keys.public');
        $this->issuer         = config('jwt.issuer');
        $this->audience       = config('jwt.audience');
        $this->ttl            = config('jwt.ttl');
    }

    public function issueToken(Admin $admin): string
    {
        $now = time();

        $payload = [
            'iss'  => $this->issuer,
            'aud'  => $this->audience,
            'sub'  => $admin->uuid,
            'typ'  => 'admin',
            'role' => $admin->role,
            'jti'  => (string) Str::uuid(),
            'iat'  => $now,
            'exp'  => $now + $this->ttl,
        ];

        try {
            $privateKey = file_get_contents($this->privateKeyPath);

            return JWT::encode($payload, $privateKey, 'RS256');
        } catch (\Throwable $e) {
            throw new InvalidTokenException('Failed to sign token: '.$e->getMessage(), 0, $e);
        }
    }

    public function validateToken(string $token): array
    {
        try {
            $publicKey = file_get_contents($this->publicKeyPath);

            $decoded = JWT::decode($token, new Key($publicKey, 'RS256'));
            $claims  = (array) $decoded;

            if (($claims['iss'] ?? '') !== $this->issuer) {
                throw new InvalidTokenException('Invalid issuer');
            }

            if (($claims['aud'] ?? '') !== $this->audience) {
                throw new InvalidTokenException('Invalid audience');
            }

            return $claims;
        } catch (InvalidTokenException $e) {
            throw $e;
        } catch (\Throwable $e) {
            throw new InvalidTokenException('Token validation failed: '.$e->getMessage(), 0, $e);
        }
    }
}
