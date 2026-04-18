<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Tingkat extends Model
{
    protected $table = 'tingkat';

    protected $fillable = ['nama', 'kode'];

    public function kelas(): HasMany
    {
        return $this->hasMany(Kelas::class);
    }
}
