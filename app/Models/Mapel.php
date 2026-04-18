<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Mapel extends Model
{
    protected $table = 'mapel';

    protected $fillable = ['kode', 'nama', 'jam_per_minggu', 'max_jam_per_hari'];

    public function guruMapel(): HasMany
    {
        return $this->hasMany(GuruMapel::class);
    }
}
