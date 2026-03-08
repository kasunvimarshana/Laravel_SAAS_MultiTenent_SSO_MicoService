<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Application\DTOs\CreateProductDto;
use App\Application\DTOs\UpdateProductDto;
use App\Application\Services\ProductService;
use App\Http\Requests\Product\CreateProductRequest;
use App\Http\Requests\Product\UpdateProductRequest;
use App\Http\Requests\Stock\AdjustStockRequest;
use App\Http\Resources\ApiResponse;
use App\Http\Resources\ProductResource;
use App\Http\Resources\StockMovementResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Saas\SharedKernel\Application\DTOs\PaginationDto;

/**
 * Product controller – thin controller, delegates all logic to ProductService.
 * Handles HTTP request/response; no business logic here.
 */
final class ProductController extends Controller
{
    public function __construct(private readonly ProductService $productService) {}

    /**
     * GET /api/v1/products
     * List products with filtering, searching, sorting and pagination.
     */
    public function index(Request $request): JsonResponse
    {
        $pagination = PaginationDto::fromArray($request->all());
        $filters    = $request->only(['category_id', 'status', 'warehouse_id']);

        $result = $this->productService->listProducts($pagination, $filters);

        if (method_exists($result, 'currentPage')) {
            // Paginated result
            $result->through(fn($product) => new ProductResource($product));
            return ApiResponse::paginated($result);
        }

        return ApiResponse::success(ProductResource::collection($result));
    }

    /**
     * POST /api/v1/products
     * Create a new product.
     */
    public function store(CreateProductRequest $request): JsonResponse
    {
        $dto     = CreateProductDto::fromArray($request->validated());
        $product = $this->productService->createProduct($dto);

        return ApiResponse::created(new ProductResource($product));
    }

    /**
     * GET /api/v1/products/{id}
     * Retrieve a single product.
     */
    public function show(string $id): JsonResponse
    {
        $product = $this->productService->getProduct($id);
        return ApiResponse::success(new ProductResource($product->load('category')));
    }

    /**
     * PUT /api/v1/products/{id}
     * Update an existing product.
     */
    public function update(UpdateProductRequest $request, string $id): JsonResponse
    {
        $dto     = UpdateProductDto::fromArray($request->validated());
        $product = $this->productService->updateProduct($id, $dto);

        return ApiResponse::success(new ProductResource($product));
    }

    /**
     * DELETE /api/v1/products/{id}
     * Soft-delete a product.
     */
    public function destroy(string $id): JsonResponse
    {
        $this->productService->deleteProduct($id);
        return ApiResponse::noContent();
    }

    /**
     * POST /api/v1/products/{id}/adjust-stock
     * Adjust stock level for a product.
     */
    public function adjustStock(AdjustStockRequest $request, string $id): JsonResponse
    {
        $data = $request->validated();

        $product = $this->productService->adjustStock(
            productId:    $id,
            delta:        (int) $data['delta'],
            type:         $data['type'],
            warehouseId:  $data['warehouse_id'],
            referenceType: $data['reference_type'] ?? null,
            referenceId:  $data['reference_id']   ?? null,
            notes:        $data['notes']           ?? null
        );

        return ApiResponse::success(new ProductResource($product), 'Stock adjusted successfully.');
    }
}
