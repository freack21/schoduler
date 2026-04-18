<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Jadwal extends Model
{
    protected $table = 'jadwal';

    protected $fillable = ['guru_mapel_id', 'hari', 'jam_pelajaran_id'];

    public function guruMapel(): BelongsTo
    {
        return $this->belongsTo(GuruMapel::class);
    }

    public function jamPelajaran(): BelongsTo
    {
        return $this->belongsTo(JamPelajaran::class);
    }
}
