<?php

namespace App\Services;

use App\Interfaces\RepositoryInterface;
use App\Interfaces\ServiceInterface;
use Illuminate\Database\Eloquent\Collection;

abstract class BaseService implements ServiceInterface
{
    protected RepositoryInterface $repository;

    public function __construct(RepositoryInterface $repository)
    {
        $this->repository = $repository;
    }

    public function all(): Collection
    {
        return $this->repository->all();
    }

    public function paginate(int $perPage = 15)
    {
        return $this->repository->paginate($perPage);
    }

    public function find($id)
    {
        return $this->repository->find($id);
    }

    public function create(array $data)
    {
        return $this->repository->create($data);
    }

    public function update($id, array $data)
    {
        $model = $this->repository->findOrFail($id);

        return $this->repository->update($model, $data);
    }

    public function delete($id): bool
    {
        $model = $this->repository->findOrFail($id);

        return $this->repository->delete($model);
    }
}
