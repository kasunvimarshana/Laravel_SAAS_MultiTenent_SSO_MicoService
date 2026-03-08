<?php

declare(strict_types=1);

namespace App\Application\DTOs;

final readonly class RegisterDto
{
    public function __construct(
        public readonly string  $name,
        public readonly string  $email,
        public readonly string  $password,
        public readonly ?string $deviceName = null,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            name:       $data['name'],
            email:      $data['email'],
            password:   $data['password'],
            deviceName: $data['device_name'] ?? null,
        );
    }
}
