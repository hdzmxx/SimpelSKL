<?php

namespace App\Models;

use App\Models\Scopes\YearScope;
use App\Support\Year;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Subject extends Model
{
    protected $table = 'mata_pelajaran';

    protected $fillable = ['academic_year_id', 'kode', 'nama', 'nama_rapor', 'urut', 'ada_praktik', 'bobot_semester', 'bobot_tertulis', 'bobot_praktik', 'ujian_dari_sem6', 'tampil_di_rapor', 'aktif'];

    protected $casts = [
        'ada_praktik' => 'boolean', 'ujian_dari_sem6' => 'boolean', 'tampil_di_rapor' => 'boolean', 'aktif' => 'boolean',
        'bobot_semester' => 'float', 'bobot_tertulis' => 'float', 'bobot_praktik' => 'float',
    ];

    protected static function booted(): void
    {
        static::addGlobalScope(new YearScope());
        static::creating(function (self $m) {
            if (!$m->academic_year_id) $m->academic_year_id = Year::id();
            if ($m->aktif === null) $m->aktif = true;
        });
    }

    public function scores(): HasMany
    {
        return $this->hasMany(Score::class);
    }

    /** Hanya mapel yang aktif (untuk input, laporan, ekspor, cetak). */
    public function scopeAktif(Builder $q): Builder
    {
        return $q->where('aktif', true);
    }

    /** Kolom nilai yang harus diisi untuk mapel ini. */
    public function fields(): array
    {
        $f = ['sem1', 'sem2', 'sem3', 'sem4', 'sem5', 'sem6'];
        if (!$this->ujian_dari_sem6) $f[] = 'tertulis';
        if ($this->ada_praktik) $f[] = 'praktik';
        return $f;
    }
}
