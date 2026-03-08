<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Domain\Inventory\Repositories\Contracts\WarehouseRepositoryInterface;
use App\Http\Resources\ApiResponse;
use App\Http\Resources\WarehouseResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Saas\SharedKernel\Application\DTOs\PaginationDto;

/**
 * Thin controller for warehouse management.
 */
final class WarehouseController extends Controller
{
    public function __construct(
        private readonly WarehouseRepositoryInterface $warehouseRepository
    ) {}

    public function index(Request $request): JsonResponse
    {
        $pagination = PaginationDto::fromArray($request->all());
        $result     = $this->warehouseRepository->findAll($pagination->mergeToCriteria());

        if (method_exists($result, 'currentPage')) {
            $result->through(fn($w) => new WarehouseResource($w));
            return ApiResponse::paginated($result);
        }

        return ApiResponse::success(WarehouseResource::collection($result));
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name'      => ['required', 'string', 'max:255'],
            'code'      => ['required', 'string', 'max:50'],
            'type'      => ['sometimes', 'string', 'in:main,secondary,virtual'],
            'address'   => ['sometimes', 'array'],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        $warehouse = $this->warehouseRepository->create($data);
        return ApiResponse::created(new WarehouseResource($warehouse));
    }

    public function show(string $id): JsonResponse
    {
        return ApiResponse::success(new WarehouseResource($this->warehouseRepository->findOrFail($id)));
    }

    public function update(Request $request, string $id): JsonResponse
    {
        $data      = $request->validate([
            'name'      => ['sometimes', 'string', 'max:255'],
            'type'      => ['sometimes', 'string', 'in:main,secondary,virtual'],
            'address'   => ['sometimes', 'array'],
            'is_active' => ['sometimes', 'boolean'],
        ]);
        $warehouse = $this->warehouseRepository->update($id, $data);
        return ApiResponse::success(new WarehouseResource($warehouse));
    }

    public function destroy(string $id): JsonResponse
    {
        $this->warehouseRepository->delete($id);
        return ApiResponse::noContent();
    }
}
