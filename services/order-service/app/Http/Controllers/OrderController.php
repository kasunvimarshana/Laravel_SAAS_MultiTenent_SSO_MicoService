<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Application\DTOs\CreateOrderDto;
use App\Application\Services\OrderService;
use App\Http\Resources\OrderResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Saas\SharedKernel\Application\DTOs\PaginationDto;

/**
 * Thin order controller.
 */
final class OrderController extends Controller
{
    public function __construct(private readonly OrderService $orderService) {}

    public function index(Request $request): JsonResponse
    {
        $pagination = PaginationDto::fromArray($request->all());
        $filters    = $request->only(['status', 'customer_id']);
        $result     = $this->orderService->list($pagination, $filters);

        if (method_exists($result, 'currentPage')) {
            $result->through(fn($o) => new OrderResource($o));
            return response()->json(['success' => true, 'data' => $result->items(), 'meta' => ['total' => $result->total()]]);
        }

        return response()->json(['success' => true, 'data' => OrderResource::collection($result)]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'customer_id'               => ['required', 'string'],
            'items'                     => ['required', 'array', 'min:1'],
            'items.*.product_id'        => ['required', 'uuid'],
            'items.*.product_name'      => ['required', 'string'],
            'items.*.product_sku'       => ['required', 'string'],
            'items.*.quantity'          => ['required', 'integer', 'min:1'],
            'items.*.unit_price'        => ['required', 'integer', 'min:0'],
            'items.*.warehouse_id'      => ['sometimes', 'uuid'],
            'shipping_address'          => ['sometimes', 'array'],
            'currency'                  => ['sometimes', 'string', 'size:3'],
            'shipping_cost'             => ['sometimes', 'integer', 'min:0'],
            'warehouse_id'              => ['sometimes', 'uuid'],
        ]);

        $order = $this->orderService->create(CreateOrderDto::fromArray($data));
        return response()->json(['success' => true, 'data' => new OrderResource($order->load('items'))], 201);
    }

    public function show(string $id): JsonResponse
    {
        $order = $this->orderService->get($id);
        return response()->json(['success' => true, 'data' => new OrderResource($order->load('items'))]);
    }

    public function cancel(Request $request, string $id): JsonResponse
    {
        $data  = $request->validate(['reason' => ['sometimes', 'string', 'max:500']]);
        $order = $this->orderService->cancel($id, $data['reason'] ?? '');
        return response()->json(['success' => true, 'data' => new OrderResource($order)]);
    }
}
