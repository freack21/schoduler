<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ModulAjar extends Model
{
    protected $table = 'modul_ajar';

    protected $fillable = ['guru_id', 'mapel_id', 'nama_file', 'file_path'];

    public function guru(): BelongsTo
    {
        return $this->belongsTo(Guru::class);
    }

    public function mapel(): BelongsTo
    {
        return $this->belongsTo(Mapel::class);
    }
}
