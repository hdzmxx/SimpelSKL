<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Tahun pelajaran: siswa, mapel (beserta bobotnya), dan pengaturan kelulusan menjadi milik satu tahun pelajaran.
 * Aman dijalankan di database yang sudah berisi data: semua data lama otomatis masuk ke tahun pelajaran 2025/2026.
 */
return new class extends Migration {
    /** Kunci pengaturan yang berlaku per tahun pelajaran (sisanya: identitas sekolah, berlaku umum). */
    private const YEAR_KEYS = ['kepsek', 'nip', 'sk_nomor', 'sk_tahun', 'sk_tanggal', 'tgl_lulus', 'ttd_skl', 'ttd_transkrip',
        'ttd_rapor', 'skl_mulai', 'nomor_skl', 'nomor_transkrip', 'nomor_rapor'];

    public function up(): void
    {
        Schema::create('academic_years', function (Blueprint $t) {
            $t->id();
            $t->string('nama', 20)->unique();
            $t->boolean('aktif')->default(false);
            $t->timestamps();
        });

        Schema::table('students', function (Blueprint $t) {
            $t->unsignedBigInteger('academic_year_id')->nullable()->index();
        });

        Schema::table('subjects', function (Blueprint $t) {
            $t->unsignedBigInteger('academic_year_id')->nullable()->index();
            $t->dropUnique(['kode']);
        });
        Schema::table('subjects', function (Blueprint $t) {
            $t->unique(['kode', 'academic_year_id']);
        });

        Schema::table('settings', function (Blueprint $t) {
            $t->unsignedBigInteger('academic_year_id')->nullable()->index();
            $t->dropUnique(['key']);
        });
        Schema::table('settings', function (Blueprint $t) {
            $t->unique(['key', 'academic_year_id']);
        });

        // Data yang sudah ada = tahun pelajaran 2025/2026
        $now = now();
        $id = DB::table('academic_years')->insertGetId(['nama' => '2025/2026', 'aktif' => true, 'created_at' => $now, 'updated_at' => $now]);
        DB::table('students')->whereNull('academic_year_id')->update(['academic_year_id' => $id]);
        DB::table('subjects')->whereNull('academic_year_id')->update(['academic_year_id' => $id]);

        // Pengaturan kelulusan lama -> milik 2025/2026 (kunci yang belum pernah disimpan diisi nilai bawaan lama agar cetakan tidak berubah)
        DB::table('settings')->where('key', 'tahun_ajaran')->delete();
        DB::table('settings')->whereIn('key', self::YEAR_KEYS)->whereNull('academic_year_id')->update(['academic_year_id' => $id]);
        $ada = DB::table('settings')->where('academic_year_id', $id)->pluck('key')->all();
        $bawaan = config('sekolah.pengaturan', []);
        foreach (self::YEAR_KEYS as $k) {
            if (!in_array($k, $ada, true) && isset($bawaan[$k])) {
                DB::table('settings')->insert(['key' => $k, 'value' => $bawaan[$k], 'academic_year_id' => $id, 'created_at' => $now, 'updated_at' => $now]);
            }
        }
    }

    public function down(): void
    {
        Schema::table('settings', function (Blueprint $t) {
            $t->dropUnique(['key', 'academic_year_id']);
        });
        Schema::table('settings', function (Blueprint $t) {
            $t->dropColumn('academic_year_id');
            $t->unique('key');
        });
        Schema::table('subjects', function (Blueprint $t) {
            $t->dropUnique(['kode', 'academic_year_id']);
        });
        Schema::table('subjects', function (Blueprint $t) {
            $t->dropColumn('academic_year_id');
            $t->unique('kode');
        });
        Schema::table('students', function (Blueprint $t) {
            $t->dropColumn('academic_year_id');
        });
        Schema::dropIfExists('academic_years');
    }
};
