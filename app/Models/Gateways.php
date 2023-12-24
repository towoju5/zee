<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Gateways extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'gateway_name',
        'slug',
        'status',
        'payout',
        'deposit',
        'supported_currencies'
    ];

    protected $casts = [
        'supported_currencies' => 'array'
    ];
}
