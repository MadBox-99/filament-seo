<?php

namespace Madbox99\FilamentSeo\Tests\Fixtures;

use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Foundation\Auth\User as Authenticatable;

class UnauthUser extends Authenticatable implements FilamentUser
{
    protected $guarded = [];

    protected $table = 'users';

    public function canAccessPanel(Panel $panel): bool
    {
        return false;
    }
}
