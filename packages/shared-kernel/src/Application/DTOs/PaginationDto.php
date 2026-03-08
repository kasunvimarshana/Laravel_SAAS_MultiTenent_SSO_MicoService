<?php

declare(strict_types=1);

namespace Saas\SharedKernel\Application\DTOs;

/**
 * Pagination parameters extracted from the HTTP request.
 */
final readonly class PaginationDto
{
    public function __construct(
        public readonly ?int   $perPage,
        public readonly int    $page,
        public readonly string $sortBy,
        public readonly string $sortDir,
        public readonly ?string $search,
    ) {}

    /**
     * @param  array<string, mixed> $data Request parameters
     */
    public static function fromArray(array $data): self
    {
        return new self(
            perPage: isset($data['per_page']) ? max(1, min(500, (int) $data['per_page'])) : null,
            page:    max(1, (int) ($data['page'] ?? 1)),
            sortBy:  $data['sort_by']  ?? 'created_at',
            sortDir: in_array($data['sort_dir'] ?? 'desc', ['asc', 'desc'], true)
                         ? $data['sort_dir']
                         : 'desc',
            search:  $data['search'] ?? null,
        );
    }

    /**
     * Merge pagination params back into a criteria array for the repository.
     *
     * @param  array<string, mixed> $criteria
     * @return array<string, mixed>
     */
    public function mergeToCriteria(array $criteria = []): array
    {
        $merged = array_merge($criteria, [
            'sort_by'  => $this->sortBy,
            'sort_dir' => $this->sortDir,
        ]);

        if ($this->perPage !== null) {
            $merged['per_page'] = $this->perPage;
            $merged['page']     = $this->page;
        }

        if ($this->search !== null) {
            $merged['search'] = $this->search;
        }

        return $merged;
    }
}
