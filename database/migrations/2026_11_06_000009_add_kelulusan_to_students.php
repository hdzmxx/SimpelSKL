<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Sikap/perilaku dan penetapan kelulusan manual (hasil rapat dewan guru) per siswa. */
return new class extends Migration {
    public function up(): void
    {
        Schema::table('students', function (Blueprint $t) {
            $t->string('sikap', 12)->nullable();           // Sangat Baik / Baik / Cukup / Kurang
            $t->string('status_lulus', 8)->nullable();     // lulus / tidak; null = ikut hasil hitungan
            $t->string('catatan_lulus')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('students', function (Blueprint $t) {
            $t->dropColumn(['sikap', 'status_lulus', 'catatan_lulus']);
        });
    }
};
