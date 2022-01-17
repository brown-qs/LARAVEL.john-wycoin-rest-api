<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Portfolio extends Model
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
        'api_id'
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array
     */
    protected $hidden = [];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
    public function custom_transactions()
    {
        return $this->hasMany(CustomTransaction::class);
    }
    public function custom_coins()
    {
        return $this->custom_transactions()->groupBy(['coin', 'coin_label', 'coin_img'])
            ->selectRaw('coin, coin_label, coin_img, sum(quantity) as quantity');
    }
}
