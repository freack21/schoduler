<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Kurikulum extends Model
{
    protected $table = 'kurikulum';
    protected $guarded = [];

    public function tingkat()
    {
        return $this->belongsTo(Tingkat::class);
    }

    public function jurusan()
    {
        return $this->belongsTo(Jurusan::class);
    }

    public function mapel()
    {
        return $this->belongsTo(Mapel::class);
    }
}
