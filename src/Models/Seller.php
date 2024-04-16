<?php

namespace TremulantTech\LaravelWalmartApi\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Seller extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'walmart_api_sellers';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = ['name'];

    /**
     * Get all of Credential models for the Seller.
     *
     * @return HasMany
     */
    public function credentials(): HasMany
    {
        return $this->hasMany(Credentials::class);
    }
}
