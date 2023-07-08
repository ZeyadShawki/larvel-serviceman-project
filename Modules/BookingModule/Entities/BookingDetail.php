<?php

namespace Modules\BookingModule\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\ServiceManagement\Entities\Service;
use Modules\ServiceManagement\Entities\Variation;
use Modules\UserManagement\Entities\User;

class BookingDetail extends Model
{
    use HasFactory;

    protected $casts = [
        'quantity' => 'integer',
        'service_cost' => 'float',
        'discount_amount' => 'float',
        'tax_amount' => 'float',
        'total_cost' => 'float',
        'campaign_discount_amount' => 'float',
        'overall_coupon_discount_amount' => 'float',
    ];

    protected $fillable = [];

    public function service(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Service::class, 'service_id');
    }

    public function variation(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Variation::class, 'variant_key', 'variant_key');
    }

    protected static function newFactory()
    {
        return \Modules\BookingModule\Database\factories\BookingDetailFactory::new();
    }
}
