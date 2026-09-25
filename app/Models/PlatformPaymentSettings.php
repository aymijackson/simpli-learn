<?php

namespace App\Models;

use App\Enums\PlatformPaymentMode;
use Illuminate\Database\Eloquent\Model;

class PlatformPaymentSettings extends Model
{
    protected $fillable = ['mode'];

    protected function casts(): array
    {
        return [
            'mode' => PlatformPaymentMode::class,
        ];
    }

    public static function current(): self
    {
        return static::firstOrCreate(['id' => 1], ['mode' => PlatformPaymentMode::TenantManaged->value]);
    }

    public function isPlatformManaged(): bool
    {
        return $this->mode === PlatformPaymentMode::PlatformManaged;
    }
}
