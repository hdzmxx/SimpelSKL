<?php

namespace Database\Seeders;

use App\Models\AcademicYear;
use App\Models\Score;
use App\Models\Student;
use App\Models\Subject;
use App\Models\User;
use App\Support\Year;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use RuntimeException;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        if (app()->environment('production') && config('sekolah.seed_sample_data')) {
            throw new RuntimeException('SEED_SAMPLE_DATA tidak boleh diaktifkan di production.');
        }

        $adminEmail = config('sekolah.admin.email');
        $adminPassword = config('sekolah.admin.password');

        if (blank($adminEmail) || blank($adminPassword)) {
            throw new RuntimeException('Set ADMIN_EMAIL dan ADMIN_PASSWORD sebelum menjalankan seeder.');
        }

        if (app()->environment('production') && mb_strlen($adminPassword) < 12) {
            throw new RuntimeException('ADMIN_PASSWORD harus memiliki minimal 12 karakter di production.');
        }

        User::updateOrCreate(
            ['email' => $adminEmail],
            ['name' => 'Administrator', 'password' => Hash::make($adminPassword)]
        );

        // Tahun pelajaran awal (migrasi sudah membuatnya; ini hanya jaring pengaman)
        if (AcademicYear::count() === 0) {
            AcademicYear::create(['nama' => '2025/2026', 'aktif' => true]);
        }
        Year::forget();

        if (Subject::count() === 0) {
            foreach (config('sekolah.mapel') as $i => $m) {
                Subject::create([
                    'kode' => $m[0], 'nama' => $m[1], 'nama_rapor' => $m[2], 'urut' => $i + 1, 'ada_praktik' => $m[3],
                    'bobot_semester' => $m[4], 'bobot_tertulis' => $m[5], 'bobot_praktik' => $m[6],
                    'ujian_dari_sem6' => $m[7], 'tampil_di_rapor' => $m[8],
                ]);
            }
        }

        // Data siswa + nilai hanya dimuat jika SEED_SAMPLE_DATA=true di lingkungan non-production.
        $file = database_path('seeders/data/siswa.json');
        if (config('sekolah.seed_sample_data') && Student::count() === 0 && is_file($file)) {
            $subjects = Subject::pluck('id', 'kode');
            foreach (json_decode(file_get_contents($file), true) as $row) {
                $st = Student::create([
                    'no_urut' => $row['no_urut'], 'nisn' => $row['nisn'], 'nis' => $row['nis'], 'nama' => $row['nama'],
                    'kelas' => $row['kelas'], 'tempat_lahir' => $row['tempat_lahir'], 'tgl_lahir' => $row['tgl_lahir'],
                    'no_ijazah' => $row['no_ijazah'], 'absensi' => $row['absensi'],
                ]);
                foreach ($row['nilai'] as $kode => $n) {
                    if (!isset($subjects[$kode])) continue;
                    $a = ['student_id' => $st->id, 'subject_id' => $subjects[$kode], 'tertulis' => $n['t'], 'praktik' => $n['p']];
                    foreach ($n['s'] as $k => $v) $a['sem' . ($k + 1)] = $v;
                    Score::create($a);
                }
            }
        }
    }
}
