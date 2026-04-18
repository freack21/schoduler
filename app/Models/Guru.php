<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Guru extends Model
{
    protected $table = 'guru';

    protected $fillable = ['user_id'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }

    public function guruMapel(): HasMany
    {
        return $this->hasMany(GuruMapel::class);
    }

    public function mapel()
    {
        return $this->belongsToMany(Mapel::class, 'guru_mapel')
                    ->withPivot('kelas_id')
                    ->withTimestamps();
    }
}
