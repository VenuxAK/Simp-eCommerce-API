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

    public function paginate(int $perPage = 20): iterable
    {
        return $this->model()::paginate($perPage);
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
}
