<?php

declare(strict_types=1);

namespace App\Application\Services;

use App\Domain\Saga\Models\SagaTransaction;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Saas\SharedKernel\Application\DTOs\PaginationDto;

/**
 * Read-side service for querying saga execution history.
 * Acts as an observability and audit API for distributed transactions.
 */
final class SagaRegistryService
{
    /**
     * @return Collection<int, SagaTransaction>|LengthAwarePaginator
     */
    public function list(PaginationDto $pagination, array $filters = []): Collection|LengthAwarePaginator
    {
        $query = SagaTransaction::query();

        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }
        if (!empty($filters['saga_type'])) {
            $query->where('saga_type', $filters['saga_type']);
        }
        if (!empty($filters['tenant_id'])) {
            $query->where('tenant_id', $filters['tenant_id']);
        }

        if ($pagination->perPage !== null) {
            return $query->orderBy('created_at', 'desc')
                ->paginate($pagination->perPage, ['*'], 'page', $pagination->page);
        }

        return $query->orderBy('created_at', 'desc')->get();
    }

    public function get(string $id): SagaTransaction
    {
        return SagaTransaction::with('steps')->findOrFail($id);
    }

    /**
     * Record the start of a saga transaction (called by microservices).
     */
    public function startTransaction(string $tenantId, string $sagaType, array $context = []): SagaTransaction
    {
        return SagaTransaction::create([
            'tenant_id' => $tenantId,
            'saga_type' => $sagaType,
            'status'    => 'running',
            'context'   => $context,
        ]);
    }

    /**
     * Mark a saga transaction as completed.
     */
    public function completeTransaction(string $id, array $finalContext = []): SagaTransaction
    {
        $transaction = SagaTransaction::findOrFail($id);
        $transaction->update([
            'status'       => 'completed',
            'context'      => array_merge($transaction->context ?? [], $finalContext),
            'completed_at' => now(),
        ]);
        return $transaction->fresh();
    }

    /**
     * Mark a saga as failed and record the error.
     */
    public function failTransaction(string $id, string $error): SagaTransaction
    {
        $transaction = SagaTransaction::findOrFail($id);
        $transaction->update([
            'status'         => 'compensated',
            'error_message'  => $error,
            'compensated_at' => now(),
        ]);
        return $transaction->fresh();
    }
}
