<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Application\Services\NotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class NotificationController extends Controller
{
    public function __construct(private readonly NotificationService $notificationService) {}

    public function index(Request $request): JsonResponse
    {
        $tenantId = $request->header('X-Tenant-ID', '');
        $result   = $this->notificationService->list($tenantId, $request->only(['per_page', 'page']));

        return response()->json(['success' => true, 'data' => $result]);
    }

    public function send(Request $request): JsonResponse
    {
        $data = $request->validate([
            'type'      => ['required', 'string'],
            'channel'   => ['required', 'string', 'in:email,webhook,in_app'],
            'payload'   => ['required', 'array'],
            'recipient' => ['required', 'array'],
            'user_id'   => ['sometimes', 'nullable', 'string'],
        ]);

        $tenantId     = $request->header('X-Tenant-ID', '');
        $notification = $this->notificationService->send(
            $tenantId,
            $data['type'],
            $data['channel'],
            $data['payload'],
            $data['recipient'],
            $data['user_id'] ?? null
        );

        return response()->json(['success' => true, 'data' => $notification], 201);
    }
}
