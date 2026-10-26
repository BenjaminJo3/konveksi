<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RolePermission extends Model
{
    protected $fillable = ['role', 'permission_key', 'allowed', 'updated_by'];

    protected function casts(): array
    {
        return ['allowed' => 'boolean'];
    }
}
