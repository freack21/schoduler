<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class JamPelajaran extends Model
{
    protected $table = 'jam_pelajaran';

    protected $fillable = ['hari', 'jam_ke', 'jam_mulai', 'jam_selesai', 'is_istirahat', 'nama_kegiatan', 'durasi_menit'];

    protected $casts = [
        'is_istirahat' => 'boolean',
    ];

    public function jadwal(): HasMany
    {
        return $this->hasMany(Jadwal::class);
    }

    public function getRentangAttribute(): string
    {
        return substr($this->jam_mulai, 0, 5) . ' - ' . substr($this->jam_selesai, 0, 5);
    }
}
