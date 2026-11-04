<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AcademicYear extends Model
{
    protected $table = 'tahun_ajaran';

    protected $fillable = ['nama', 'aktif'];
    protected $casts = ['aktif' => 'boolean'];

    public function jumlahSiswa(): int
    {
        return Student::withoutGlobalScopes()->where('academic_year_id', $this->id)->count();
    }

    public function jumlahMapel(): int
    {
        return Subject::withoutGlobalScopes()->where('academic_year_id', $this->id)->count();
    }
}
