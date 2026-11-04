<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/** Semua mapel (semua tahun pelajaran) memakai PSAJ tertulis sendiri; nilai Semester 6 tidak lagi menggantikan ujian IPA. */
return new class extends Migration {
    public function up(): void
    {
        DB::table('subjects')->update(['ujian_dari_sem6' => false]);
    }

    public function down(): void
    {
        // Tidak dipulihkan: sebelumnya hanya mapel IPA yang memakai opsi ini.
        DB::table('subjects')->where('kode', 'IPA')->update(['ujian_dari_sem6' => true]);
    }
};
