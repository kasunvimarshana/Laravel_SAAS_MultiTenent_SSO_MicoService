<?php

declare(strict_types=1);

namespace App\Application\Services;

use App\Application\DTOs\LoginDto;
use App\Application\DTOs\RegisterDto;
use App\Domain\Auth\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Saas\SharedKernel\Domain\Exceptions\UnauthorizedException;
use Saas\SharedKernel\Domain\Exceptions\ValidationException;
use Saas\SharedKernel\Infrastructure\MessageBroker\Contracts\MessageBrokerInterface;
use Saas\SharedKernel\Infrastructure\Tenant\TenantContext;

/**
 * Authentication application service.
 * Handles registration, login, logout, and token refresh.
 */
final class AuthService
{
    public function __construct(
        private readonly MessageBrokerInterface $messageBroker,
        private readonly TenantContext          $tenantContext
    ) {}

    /**
     * Register a new user and issue an access token.
     *
     * @return array{user: User, token: string, token_type: string}
     */
    public function register(RegisterDto $dto): array
    {
        return DB::transaction(function () use ($dto): array {
            // Check email uniqueness within the tenant
            $exists = User::where('tenant_id', $this->tenantContext->getTenantId())
                ->where('email', $dto->email)
                ->exists();

            if ($exists) {
                throw new ValidationException(['email' => ['This email is already registered.']]);
            }

            $user = User::create([
                'tenant_id' => $this->tenantContext->getTenantId(),
                'name'      => $dto->name,
                'email'     => $dto->email,
                'password'  => $dto->password, // already hashed via cast
                'status'    => 'active',
            ]);

            // Assign default tenant role
            $user->assignRole('tenant-user');

            $token = $user->createToken($dto->deviceName ?? 'web')->accessToken;

            $this->messageBroker->publish('auth.user.registered', [
                'tenant_id' => $this->tenantContext->getTenantId(),
                'user_id'   => $user->id,
                'email'     => $user->email,
            ]);

            return [
                'user'       => $user,
                'token'      => $token,
                'token_type' => 'Bearer',
            ];
        });
    }

    /**
     * Authenticate a user and return an access token.
     *
     * @return array{user: User, token: string, token_type: string}
     */
    public function login(LoginDto $dto): array
    {
        $user = User::where('tenant_id', $this->tenantContext->getTenantId())
            ->where('email', $dto->email)
            ->first();

        if (!$user || !Hash::check($dto->password, $user->password)) {
            throw new UnauthorizedException('authenticate with the provided credentials');
        }

        if (!$user->isActive()) {
            throw new UnauthorizedException('access this account – it is suspended or inactive');
        }

        $token = $user->createToken($dto->deviceName ?? 'web')->accessToken;

        $this->messageBroker->publish('auth.user.logged_in', [
            'tenant_id' => $this->tenantContext->getTenantId(),
            'user_id'   => $user->id,
        ]);

        return [
            'user'       => $user,
            'token'      => $token,
            'token_type' => 'Bearer',
        ];
    }

    /**
     * Revoke the current user's access token (logout).
     */
    public function logout(User $user): void
    {
        $user->token()->revoke();

        $this->messageBroker->publish('auth.user.logged_out', [
            'tenant_id' => $this->tenantContext->getTenantId(),
            'user_id'   => $user->id,
        ]);
    }

    /**
     * Return the currently authenticated user with roles/permissions.
     */
    public function me(User $user): User
    {
        return $user->load('roles', 'permissions');
    }
}
