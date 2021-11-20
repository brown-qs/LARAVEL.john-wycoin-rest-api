<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserExchange extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var string[]
     */
    protected $fillable = [
        'user_id',
        'title',
        'exchange',
        'metadata',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array
     */
    protected $hidden = [
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
    ];

    public function user() {
        return $this->belongsTo(User::class);
    }
}
