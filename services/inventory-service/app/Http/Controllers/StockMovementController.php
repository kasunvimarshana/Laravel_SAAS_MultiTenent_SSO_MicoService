<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Domain\Inventory\Repositories\Contracts\StockMovementRepositoryInterface;
use App\Http\Resources\ApiResponse;
use App\Http\Resources\StockMovementResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Saas\SharedKernel\Application\DTOs\PaginationDto;

/**
 * Read-only controller for stock movement audit trail.
 * Movements are created by ProductService; not directly through this controller.
 */
final class StockMovementController extends Controller
{
    public function __construct(
        private readonly StockMovementRepositoryInterface $movementRepository
    ) {}

    public function index(Request $request): JsonResponse
    {
        $pagination = PaginationDto::fromArray($request->all());
        $filters    = $request->only(['type', 'warehouse_id', 'product_id']);
        $result     = $this->movementRepository->findAll(
            $pagination->mergeToCriteria(['filters' => $filters])
        );

        if (method_exists($result, 'currentPage')) {
            $result->through(fn($m) => new StockMovementResource($m));
            return ApiResponse::paginated($result);
        }

        return ApiResponse::success(StockMovementResource::collection($result));
    }

    public function show(string $id): JsonResponse
    {
        return ApiResponse::success(
            new StockMovementResource($this->movementRepository->findOrFail($id))
        );
    }

    public function byProduct(Request $request, string $productId): JsonResponse
    {
        $pagination = PaginationDto::fromArray($request->all());
        $result     = $this->movementRepository->findByProduct(
            $productId,
            $pagination->mergeToCriteria()
        );

        if (method_exists($result, 'currentPage')) {
            $result->through(fn($m) => new StockMovementResource($m));
            return ApiResponse::paginated($result);
        }

        return ApiResponse::success(StockMovementResource::collection($result));
    }
}
