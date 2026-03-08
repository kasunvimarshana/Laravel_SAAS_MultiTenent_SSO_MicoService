<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Application\Services\SagaRegistryService;
use App\Http\Resources\SagaTransactionResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Saas\SharedKernel\Application\DTOs\PaginationDto;

/**
 * Saga audit/observability controller.
 */
final class SagaController extends Controller
{
    public function __construct(private readonly SagaRegistryService $sagaService) {}

    public function index(Request $request): JsonResponse
    {
        $pagination = PaginationDto::fromArray($request->all());
        $filters    = $request->only(['status', 'saga_type', 'tenant_id']);
        $result     = $this->sagaService->list($pagination, $filters);

        if (method_exists($result, 'currentPage')) {
            return response()->json([
                'success' => true,
                'data'    => SagaTransactionResource::collection($result->items()),
                'meta'    => ['total' => $result->total()],
            ]);
        }

        return response()->json([
            'success' => true,
            'data'    => SagaTransactionResource::collection($result),
        ]);
    }

    public function show(string $id): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data'    => new SagaTransactionResource($this->sagaService->get($id)),
        ]);
    }

    /**
     * POST /v1/sagas – called by microservices to register saga start
     */
    public function start(Request $request): JsonResponse
    {
        $data = $request->validate([
            'tenant_id' => ['required', 'string'],
            'saga_type' => ['required', 'string', 'max:100'],
            'context'   => ['sometimes', 'array'],
        ]);

        $transaction = $this->sagaService->startTransaction(
            $data['tenant_id'],
            $data['saga_type'],
            $data['context'] ?? []
        );

        return response()->json(['success' => true, 'data' => new SagaTransactionResource($transaction)], 201);
    }

    /**
     * PUT /v1/sagas/{id}/complete
     */
    public function complete(Request $request, string $id): JsonResponse
    {
        $data        = $request->validate(['context' => ['sometimes', 'array']]);
        $transaction = $this->sagaService->completeTransaction($id, $data['context'] ?? []);
        return response()->json(['success' => true, 'data' => new SagaTransactionResource($transaction)]);
    }

    /**
     * PUT /v1/sagas/{id}/fail
     */
    public function fail(Request $request, string $id): JsonResponse
    {
        $data        = $request->validate(['error' => ['required', 'string']]);
        $transaction = $this->sagaService->failTransaction($id, $data['error']);
        return response()->json(['success' => true, 'data' => new SagaTransactionResource($transaction)]);
    }
}
