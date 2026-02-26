<?php

namespace App\Repositories;

use App\Models\Contract;

class ContractRepository implements ContractRepositoryInterface
{
    /**
     * Find a contract by ID.
     */
    public function findById(int $id): ?Contract
    {
        return Contract::find($id);
    }

    /**
     * Get a contract by ID or throw.
     */
    public function getByIdOrFail(int $id): Contract
    {
        return Contract::findOrFail($id);
    }
}
