<?php

namespace App\Interfaces;

use Illuminate\Database\Eloquent\Collection;

interface RepositoryInterface
{
    public function all(): Collection;

    public function paginate(int $perPage = 15);

    public function find($id);

    public function findOrFail($id);

    public function create(array $data);

    public function update($model, array $data);

    public function delete($model): bool;

    public function query();
}
