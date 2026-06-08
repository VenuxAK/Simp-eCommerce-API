<?php

namespace App\Modules\Core\Repositories;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * Base repository encapsulating Eloquent data access.
 *
 * Subclasses define domain-specific queries while inheriting
 * common CRUD operations. Keeps query logic out of services
 * and controllers so data access patterns are centralized.
 */
abstract class Repository
{
    /** Maximum items per page to prevent unbounded result sets. */
    protected const MAX_PER_PAGE = 100;

    /** Default items per page across the application. */
    protected const DEFAULT_PER_PAGE = 20;

    abstract protected function model(): string;

    public function find(int $id): ?Model
    {
        return $this->model()::find($id);
    }

    public function findOrFail(int $id): Model
    {
        return $this->model()::findOrFail($id);
    }

    public function all(): iterable
    {
        return $this->model()::all();
    }

    public function paginate(int $perPage = self::DEFAULT_PER_PAGE): iterable
    {
        return $this->model()::paginate($this->clampPerPage($perPage));
    }

    public function create(array $data): Model
    {
        return $this->model()::create($data);
    }

    public function update(Model $model, array $data): bool
    {
        return $model->update($data);
    }

    public function delete(Model $model): ?bool
    {
        return $model->delete();
    }

    public function query(): Builder
    {
        return $this->model()::query();
    }

    /**
     * Clamp per_page to a safe range [1, MAX_PER_PAGE].
     *
     * Prevents clients from requesting 0 or extremely large
     * page sizes that could cause memory or performance issues.
     */
    protected function clampPerPage(int $perPage): int
    {
        return max(1, min($perPage, static::MAX_PER_PAGE));
    }
}
