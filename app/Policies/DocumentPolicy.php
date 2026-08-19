<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Document;
use App\Models\User;

final class DocumentPolicy
{
    /**
     * Determine whether the user can view the document.
     */
    public function view(User $user, Document $document): bool
    {
        return $user->id === $document->user_id;
    }

    /**
     * Determine whether the user can update the document.
     */
    public function update(User $user, Document $document): bool
    {
        return $user->id === $document->user_id;
    }

    /**
     * Determine whether the user can delete the document.
     */
    public function delete(User $user, Document $document): bool
    {
        return $user->id === $document->user_id;
    }
}
