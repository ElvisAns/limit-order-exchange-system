<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'balance',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'balance' => 'decimal:2',
        ];
    }

    /**
     * Get the assets for the user.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany<Asset>
     */
    public function assets(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Asset::class);
    }

    /**
     * Get the orders for the user.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany<Order>
     */
    public function orders(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Order::class);
    }

    /**
     * Get the buy trades for the user.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany<Trade>
     */
    public function buyTrades(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Trade::class, 'buyer_id');
    }

    /**
     * Get the sell trades for the user.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany<Trade>
     */
    public function sellTrades(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Trade::class, 'seller_id');
    }
}
