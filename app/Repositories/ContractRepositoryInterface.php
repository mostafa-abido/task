<?php

namespace App\Repositories;

use App\Models\Contract;

interface ContractRepositoryInterface
{
    /**
     * Find a contract by ID.
     */
    public function findById(int $id): ?Contract;

    /**
     * Get a contract by ID or throw.
     */
    public function getByIdOrFail(int $id): Contract;
}
