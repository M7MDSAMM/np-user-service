<?php

namespace App\Domain\Auth;

use App\Domain\Admin\Admin;

interface JwtTokenServiceInterface
{
    /**
     * Issue a signed JWT for the given admin.
     */
    public function issueToken(Admin $admin): string;

    /**
     * Validate and decode a JWT. Returns the claims array.
     *
     * @throws \App\Domain\Auth\InvalidTokenException
     */
    public function validateToken(string $token): array;
}
