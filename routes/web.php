<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DocumentController;
use App\Http\Controllers\KelulusanController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\ScoreController;
use App\Http\Controllers\SemesterController;
use App\Http\Controllers\SettingController;
use App\Http\Controllers\StudentController;
use App\Http\Controllers\SubjectController;
use App\Http\Controllers\TransferController;
use App\Http\Controllers\YearController;
use Illuminate\Support\Facades\Route;

Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:10,1');
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

Route::middleware('auth')->group(function () {
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

    // Tahun pelajaran
    Route::get('tahun', [YearController::class, 'index'])->name('tahun.index');
    Route::post('tahun', [YearController::class, 'store'])->name('tahun.store');
    Route::post('tahun/pilih', [YearController::class, 'select'])->name('tahun.pilih');
    Route::post('tahun/{year}/aktif', [YearController::class, 'activate'])->name('tahun.aktif');
    Route::delete('tahun/{year}', [YearController::class, 'destroy'])->name('tahun.hapus');

    // Database siswa
    Route::get('kehadiran', [StudentController::class, 'attendance'])->name('kehadiran');
    Route::get('siswa-ekspor', [StudentController::class, 'export'])->name('siswa.ekspor');
    Route::post('siswa-impor', [StudentController::class, 'import'])->name('siswa.impor');
    Route::resource('siswa', StudentController::class)->parameters(['siswa' => 'student'])->except(['show']);

    // Mapel & bobot
    Route::post('mapel/{subject}/{arah}', [SubjectController::class, 'move'])->whereIn('arah', ['naik', 'turun'])->name('mapel.pindah');
    Route::post('mapel/{subject}/toggle-aktif', [SubjectController::class, 'toggle'])->name('mapel.aktif');
    Route::post('mapel-bobot', [SubjectController::class, 'applyBobot'])->name('mapel.bobot');
    Route::resource('mapel', SubjectController::class)->parameters(['mapel' => 'subject'])->except(['show']);

    // Nilai per mapel: input, ekspor, impor
    Route::get('nilai/{subject}', [ScoreController::class, 'grid'])->name('nilai.grid');
    Route::post('nilai/{subject}', [ScoreController::class, 'save'])->name('nilai.save');
    Route::get('nilai/{subject}/ekspor', [ScoreController::class, 'export'])->name('nilai.ekspor');
    Route::post('nilai/{subject}/impor', [ScoreController::class, 'import'])->name('nilai.impor');

    // Impor/ekspor semua mapel (satu sheet per mapel) + pratinjau impor
    Route::get('impor-ekspor', [TransferController::class, 'index'])->name('transfer');
    Route::get('impor-ekspor/ekspor', [TransferController::class, 'exportAll'])->name('transfer.ekspor');
    Route::get('impor-ekspor/ekspor-1sheet', [TransferController::class, 'exportLeger'])->name('transfer.ekspor.leger');
    Route::get('impor-ekspor/ekspor-riwayat', [TransferController::class, 'exportLegerRiwayat'])->name('transfer.ekspor.riwayat');
    Route::get('kehadiran/ekspor', [TransferController::class, 'exportAttendance'])->name('transfer.ekspor.kehadiran');
    Route::post('impor-ekspor/impor', [TransferController::class, 'importAll'])->name('transfer.impor');
    Route::post('impor-ekspor/impor-1sheet', [TransferController::class, 'importLeger'])->name('transfer.impor.leger');
    Route::post('impor-ekspor/impor-riwayat', [TransferController::class, 'importLegerRiwayat'])->name('transfer.impor.riwayat');
    Route::post('kehadiran/impor', [TransferController::class, 'importAttendance'])->name('transfer.impor.kehadiran');
    Route::get('impor/pratinjau', [TransferController::class, 'preview'])->name('impor.pratinjau');
    Route::post('impor/terapkan', [TransferController::class, 'apply'])->name('impor.terapkan');
    Route::post('impor/batal', [TransferController::class, 'cancel'])->name('impor.batal');

    // Laporan
    Route::get('master', [ReportController::class, 'master'])->name('master');
    Route::get('nilai-semester', [SemesterController::class, 'index'])->name('semester');
    Route::post('nilai-semester/urutan', [SemesterController::class, 'simpanUrutan'])->name('semester.urutan');
    Route::get('nilai-semester/cetak', [SemesterController::class, 'cetak'])->name('semester.cetak');
    Route::post('nilai-semester/impor', [SemesterController::class, 'import'])->name('semester.impor');
    Route::get('nilai-akhir', [ReportController::class, 'akhir'])->name('akhir');
    Route::get('peringkat', [ReportController::class, 'peringkat'])->name('peringkat');

    // Kelulusan
    Route::get('kelulusan', [KelulusanController::class, 'index'])->name('kelulusan');
    Route::get('kelulusan/ekspor', [KelulusanController::class, 'ekspor'])->name('kelulusan.ekspor');
    Route::post('kelulusan/aturan', [KelulusanController::class, 'aturan'])->name('kelulusan.aturan');
    Route::post('kelulusan/sikap', [KelulusanController::class, 'sikap'])->name('kelulusan.sikap');
    Route::post('kelulusan/sikap-massal', [KelulusanController::class, 'sikapMassal'])->name('kelulusan.sikap.massal');
    Route::post('kelulusan/tetapkan', [KelulusanController::class, 'tetapkan'])->name('kelulusan.tetapkan');
    Route::get('sk-kelulusan', [DocumentController::class, 'skKelulusan'])->name('sk-kelulusan');
    Route::get('sk-kelulusan/cetak', [DocumentController::class, 'cetakSkKelulusan'])->name('sk-kelulusan.cetak');

    // Dokumen cetak
    Route::get('cetak', [DocumentController::class, 'index'])->name('cetak');
    Route::post('cetak/pengaturan', [DocumentController::class, 'simpanPengaturan'])->name('cetak.pengaturan');
    Route::get('cetak/skl/cek', [DocumentController::class, 'cekSkl'])->name('cetak.skl.cek');
    Route::get('cetak/{jenis}', [DocumentController::class, 'show'])->whereIn('jenis', ['skl', 'transkrip', 'rapor'])->name('cetak.show');

    // Pengaturan
    Route::get('pengaturan', [SettingController::class, 'index'])->name('pengaturan');
    Route::post('pengaturan', [SettingController::class, 'update'])->name('pengaturan.update');
    Route::post('pengaturan/logo/{side}', [SettingController::class, 'logo'])->name('pengaturan.logo');
    Route::delete('pengaturan/logo/{side}', [SettingController::class, 'removeLogo'])->name('pengaturan.logo.hapus');
    Route::post('pengaturan/admin/nama', [SettingController::class, 'adminName'])->name('pengaturan.admin.nama');
    Route::post('pengaturan/password', [SettingController::class, 'password'])->name('pengaturan.password');
});
