<?php

namespace App\Models;

use App\Models\Scopes\YearScope;
use App\Support\Year;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Student extends Model
{
    protected $table = 'siswa';

    protected $fillable = ['academic_year_id', 'no_urut', 'nisn', 'nis', 'nama', 'jenis_kelamin', 'kelas', 'tempat_lahir', 'tgl_lahir', 'no_ijazah', 'absensi', 'sikap', 'status_lulus', 'catatan_lulus'];

    protected $casts = ['tgl_lahir' => 'date:Y-m-d', 'absensi' => 'array'];

    protected static function booted(): void
    {
        static::addGlobalScope(new YearScope());
        static::creating(function (self $m) {
            if (!$m->academic_year_id) $m->academic_year_id = Year::id();
        });
    }

    public function scores(): HasMany
    {
        return $this->hasMany(Score::class);
    }

    public static function kelasList()
    {
        return static::query()->distinct()->orderBy('kelas')->pluck('kelas');
    }
}
