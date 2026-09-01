<?php

namespace App\Traits;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

trait BelongsToUser
{
    /**
     * Boot the trait to add creating hook and global scope
     */
    public static function bootBelongsToUser()
    {
        // When creating, automatically set the user_id if logged in
        static::creating(function ($model) {
            if (Auth::check() && !$model->user_id) {
                $model->user_id = Auth::id();
            }
        });

        // Automatically filter queries by the authenticated user's ID, unless superuser
        static::addGlobalScope('user_scope', function (Builder $builder) {
            if (Auth::check() && Auth::user()->role !== 'superuser') {
                $builder->where($builder->getQuery()->from . '.user_id', Auth::id());
            }
        });
    }

    /**
     * Relationship to the user who owns this record
     */
    public function user()
    {
        return $this->belongsTo(\App\Models\AdminUser::class, 'user_id');
    }
}
