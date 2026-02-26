<?php

namespace App\Policies;

use App\Models\ProductSale;
use App\Models\User;

class ProductSalePolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, ProductSale $productSale): bool
    {
        return $user->id === $productSale->user_id;
    }
}
