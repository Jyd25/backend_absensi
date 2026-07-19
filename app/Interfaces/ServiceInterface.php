<?php

namespace App\Interfaces;

use Illuminate\Database\Eloquent\Collection;

interface ServiceInterface
{
    public function all(): Collection;

    public function paginate(int $perPage = 15);

    public function find($id);

    public function create(array $data);

    public function update($id, array $data);

    public function delete($id): bool;
}
