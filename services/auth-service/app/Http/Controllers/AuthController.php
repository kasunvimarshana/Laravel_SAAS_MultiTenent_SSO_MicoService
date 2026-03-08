<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Application\DTOs\LoginDto;
use App\Application\DTOs\RegisterDto;
use App\Application\Services\AuthService;
use App\Http\Requests\LoginRequest;
use App\Http\Requests\RegisterRequest;
use App\Http\Resources\UserResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Thin auth controller – delegates all logic to AuthService.
 */
final class AuthController extends Controller
{
    public function __construct(private readonly AuthService $authService) {}

    /**
     * POST /api/v1/auth/register
     */
    public function register(RegisterRequest $request): JsonResponse
    {
        $result = $this->authService->register(RegisterDto::fromArray($request->validated()));

        return response()->json([
            'success' => true,
            'message' => 'Registration successful.',
            'data'    => [
                'user'         => new UserResource($result['user']),
                'access_token' => $result['token'],
                'token_type'   => $result['token_type'],
            ],
        ], 201);
    }

    /**
     * POST /api/v1/auth/login
     */
    public function login(LoginRequest $request): JsonResponse
    {
        $result = $this->authService->login(LoginDto::fromArray($request->validated()));

        return response()->json([
            'success' => true,
            'message' => 'Login successful.',
            'data'    => [
                'user'         => new UserResource($result['user']),
                'access_token' => $result['token'],
                'token_type'   => $result['token_type'],
            ],
        ]);
    }

    /**
     * POST /api/v1/auth/logout
     */
    public function logout(Request $request): JsonResponse
    {
        $this->authService->logout($request->user());
        return response()->json(['success' => true, 'message' => 'Logged out successfully.']);
    }

    /**
     * GET /api/v1/auth/me
     */
    public function me(Request $request): JsonResponse
    {
        $user = $this->authService->me($request->user());
        return response()->json(['success' => true, 'data' => new UserResource($user)]);
    }
}
