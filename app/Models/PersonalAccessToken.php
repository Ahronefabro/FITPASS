<?php

namespace App\Models;

use Laravel\Sanctum\PersonalAccessToken as SanctumToken;

/**
 * An app login. Pending sign-ups are hidden from member queries by default, but
 * they still log in to see that they are waiting, so their tokens must find them.
 */
class PersonalAccessToken extends SanctumToken
{
    protected $table = 'personal_access_tokens';

    public function tokenable()
    {
        return $this->morphTo('tokenable')->withoutGlobalScope('approved');
    }
}
