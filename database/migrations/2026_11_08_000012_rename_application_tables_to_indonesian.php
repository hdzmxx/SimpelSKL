<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::rename('academic_years', 'tahun_ajaran');
        Schema::rename('scores', 'nilai');
        Schema::rename('settings', 'pengaturan');
        Schema::rename('students', 'siswa');
        Schema::rename('subjects', 'mata_pelajaran');
        Schema::rename('users', 'pengguna');
    }

    public function down(): void
    {
        Schema::rename('pengguna', 'users');
        Schema::rename('mata_pelajaran', 'subjects');
        Schema::rename('siswa', 'students');
        Schema::rename('pengaturan', 'settings');
        Schema::rename('nilai', 'scores');
        Schema::rename('tahun_ajaran', 'academic_years');
    }
};
