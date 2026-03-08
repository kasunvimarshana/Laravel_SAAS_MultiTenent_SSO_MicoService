<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Application\Services\TenantService;
use App\Http\Resources\TenantResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Saas\SharedKernel\Application\DTOs\PaginationDto;

/**
 * Thin tenant management controller.
 */
final class TenantController extends Controller
{
    public function __construct(private readonly TenantService $tenantService) {}

    public function index(Request $request): JsonResponse
    {
        $pagination = PaginationDto::fromArray($request->all());
        $result     = $this->tenantService->list($pagination, $request->only(['status', 'plan']));

        if (method_exists($result, 'currentPage')) {
            $result->through(fn($t) => new TenantResource($t));
            return response()->json(['success' => true, 'data' => $result->items(), 'meta' => ['total' => $result->total()]]);
        }

        return response()->json(['success' => true, 'data' => TenantResource::collection($result)]);
    }

    public function store(Request $request): JsonResponse
    {
        $data   = $request->validate([
            'name'  => ['required', 'string', 'max:255'],
            'slug'  => ['required', 'string', 'max:100', 'regex:/^[a-z0-9-]+$/'],
            'plan'  => ['sometimes', 'string', 'in:free,starter,professional,enterprise'],
            'domain'=> ['sometimes', 'nullable', 'url'],
        ]);

        $tenant = $this->tenantService->create($data);
        return response()->json(['success' => true, 'data' => new TenantResource($tenant)], 201);
    }

    public function show(string $id): JsonResponse
    {
        return response()->json(['success' => true, 'data' => new TenantResource($this->tenantService->get($id))]);
    }

    public function update(Request $request, string $id): JsonResponse
    {
        $data   = $request->validate([
            'name'   => ['sometimes', 'string', 'max:255'],
            'plan'   => ['sometimes', 'string', 'in:free,starter,professional,enterprise'],
            'status' => ['sometimes', 'string', 'in:active,suspended,trial,cancelled'],
            'domain' => ['sometimes', 'nullable', 'url'],
        ]);

        $tenant = $this->tenantService->update($id, $data);
        return response()->json(['success' => true, 'data' => new TenantResource($tenant)]);
    }

    public function destroy(string $id): JsonResponse
    {
        $this->tenantService->delete($id);
        return response()->json(['success' => true, 'message' => 'Tenant deleted.']);
    }

    public function updateConfig(Request $request, string $id): JsonResponse
    {
        $config = $request->validate(['config' => ['required', 'array']]);
        $tenant = $this->tenantService->updateConfig($id, $config['config']);
        return response()->json(['success' => true, 'data' => new TenantResource($tenant)]);
    }
}
