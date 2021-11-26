<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CustomTransaction extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var string[]
     */
    // protected $fillable = [
    // ];
    protected $guarded = ['id'];

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

    public function portfolio() {
        return $this->belongsTo(Portfolio::class);
    }
}
