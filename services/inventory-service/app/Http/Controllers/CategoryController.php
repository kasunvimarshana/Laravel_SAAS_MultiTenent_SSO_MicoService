<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Domain\Inventory\Repositories\Contracts\CategoryRepositoryInterface;
use App\Http\Resources\ApiResponse;
use App\Http\Resources\CategoryResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Saas\SharedKernel\Application\DTOs\PaginationDto;

/**
 * Thin controller for category CRUD – delegates to repository directly
 * (categories do not need complex business logic at this stage).
 */
final class CategoryController extends Controller
{
    public function __construct(
        private readonly CategoryRepositoryInterface $categoryRepository
    ) {}

    public function index(Request $request): JsonResponse
    {
        $pagination = PaginationDto::fromArray($request->all());
        $result     = $this->categoryRepository->findAll($pagination->mergeToCriteria());

        if (method_exists($result, 'currentPage')) {
            $result->through(fn($cat) => new CategoryResource($cat));
            return ApiResponse::paginated($result);
        }

        return ApiResponse::success(CategoryResource::collection($result));
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name'        => ['required', 'string', 'max:255'],
            'slug'        => ['required', 'string', 'max:100'],
            'description' => ['sometimes', 'nullable', 'string'],
            'parent_id'   => ['sometimes', 'nullable', 'uuid'],
            'is_active'   => ['sometimes', 'boolean'],
        ]);

        $category = $this->categoryRepository->create($data);
        return ApiResponse::created(new CategoryResource($category));
    }

    public function show(string $id): JsonResponse
    {
        $category = $this->categoryRepository->findOrFail($id);
        return ApiResponse::success(new CategoryResource($category->load('children')));
    }

    public function update(Request $request, string $id): JsonResponse
    {
        $data = $request->validate([
            'name'        => ['sometimes', 'string', 'max:255'],
            'description' => ['sometimes', 'nullable', 'string'],
            'is_active'   => ['sometimes', 'boolean'],
        ]);

        $category = $this->categoryRepository->update($id, $data);
        return ApiResponse::success(new CategoryResource($category));
    }

    public function destroy(string $id): JsonResponse
    {
        $this->categoryRepository->delete($id);
        return ApiResponse::noContent();
    }
}
