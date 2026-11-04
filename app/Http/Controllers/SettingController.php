<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class SettingController extends Controller
{
    /** [judul grup => [kunci => label]] */
    public const GROUPS = [
        'Identitas sekolah & kop surat (berlaku untuk semua tahun pelajaran)' => [
            'nama_sekolah' => 'Nama sekolah', 'pemda' => 'Pemerintah daerah', 'dinas' => 'Dinas', 'alamat' => 'Alamat',
            'telp' => 'Telepon / faks / laman', 'email' => 'Pos-el', 'npsn' => 'NPSN', 'wilayah' => 'Wilayah (kecamatan, kabupaten, provinsi)',
            'logo_ukuran' => 'Ukuran logo kop (mm)',
        ],
    ];

    /** Pengaturan dokumen cetak (per tahun pelajaran); diubah dari menu Cetak Dokumen. */
    public const DOKUMEN = [
        'Kelulusan & tanda tangan (khusus tahun pelajaran yang dipilih)' => [
            'kepsek' => 'Nama kepala sekolah', 'nip' => 'NIP kepala sekolah', 'sk_nomor' => 'Nomor SK kelulusan', 'sk_tahun' => 'Tahun SK', 'sk_tanggal' => 'Tanggal SK',
            'tanggal_rapat' => 'Tanggal rapat dewan pendidik (SK Kelulusan)',
            'sk_menimbang' => 'Poin Menimbang (satu poin per baris)', 'sk_mengingat' => 'Poin Mengingat (satu poin per baris)',
            'tgl_lulus' => 'Tanggal kelulusan', 'ttd_skl' => 'Tempat, tanggal TTD SKL', 'ttd_transkrip' => 'Tempat, tanggal TTD Transkrip',
            'ttd_rapor' => 'Tempat, tanggal TTD Surat Nilai Rapor',
        ],
        'Penomoran dokumen, khusus tahun pelajaran yang dipilih ({no} = nomor urut, {skl} = nomor SKL, {transkrip} = nomor Transkrip, {kode}, {tahun}, {ijazah}, {nis}, {nisn})' => [
            'kode_surat' => 'Kode klasifikasi surat dari dinas ({kode}), mis. 400.3.11', 'skl_mulai' => 'Nomor SKL untuk siswa no. 1',
            'transkrip_mulai' => 'Nomor Transkrip untuk siswa no. 1', 'nomor_skl' => 'Format nomor SKL', 'nomor_transkrip' => 'Format nomor Transkrip',
            'nomor_rapor' => 'Format nomor Surat Nilai Rapor',
        ],
    ];

    public const SK_LEGAL_DEFAULTS = [
        'sk_menimbang' => "bahwa dalam rangka mengakhiri program pendidikan pada satuan pendidikan diperlukan kelulusan peserta didik kelas IX dari {sekolah};\nbahwa berdasarkan pertimbangan sebagaimana dimaksud pada huruf a, perlu menetapkan Keputusan Kepala Sekolah tentang Kelulusan Peserta Didik Kelas IX Tahun Ajaran {tahun}.",
        'sk_mengingat' => "Undang-Undang Republik Indonesia Nomor 20 Tahun 2003 tentang Sistem Pendidikan Nasional;\nUndang-Undang Republik Indonesia Nomor 23 Tahun 2014 tentang Pemerintahan Daerah;\nPeraturan Pemerintah Nomor 57 Tahun 2021 tentang Standar Nasional Pendidikan sebagaimana telah diubah dengan Peraturan Pemerintah Nomor 4 Tahun 2022;\nPeraturan Pemerintah Nomor 17 Tahun 2010 tentang Pengelolaan dan Penyelenggaraan Pendidikan dan Peraturan Pemerintah Nomor 48 Tahun 2008 tentang Pendanaan Pendidikan;\nPeraturan Menteri Pendidikan, Kebudayaan, Riset, dan Teknologi Republik Indonesia Nomor 21 Tahun 2022 tentang Standar Penilaian Pendidikan;\nPeraturan Menteri Pendidikan, Kebudayaan, Riset, dan Teknologi Republik Indonesia Nomor 58 Tahun 2024 tentang Ijazah Jenjang Pendidikan Dasar dan Pendidikan Menengah;\nKeputusan Kepala Badan Standar, Kurikulum, dan Asesmen Pendidikan Nomor 043/H/KP/2023 tentang Pedoman Penyelenggaraan Uji Kesetaraan;\nKeputusan Direktur Jenderal Pendidikan Islam Nomor 604 Tahun 2023 tentang Petunjuk Teknis Penilaian Kelulusan Peserta Didik Mata Pelajaran Pendidikan Agama Islam dan Budi Pekerti;\nPeraturan Daerah Kabupaten Kebumen Nomor 22 Tahun 2012 tentang Penyelenggaraan Pendidikan sebagaimana telah diubah dengan Peraturan Daerah Kabupaten Kebumen Nomor 4 Tahun 2016;\nKeputusan Kepala {sekolah} tentang Kurikulum Satuan Pendidikan Tahun Ajaran {tahun}.",
    ];

    public function index()
    {
        return view('settings.index', ['s' => Setting::map(), 'groups' => self::GROUPS]);
    }

    public function update(Request $r)
    {
        $keys = array_merge(...array_map('array_keys', array_values(self::GROUPS)));
        $r->validate(array_fill_keys($keys, 'nullable|string|max:500'));
        Setting::put($r->only($keys) + array_fill_keys($keys, ''));
        return back()->with('ok', 'Pengaturan disimpan.');
    }

    public function logo(Request $r, string $side)
    {
        abort_unless(in_array($side, ['kiri', 'kanan', 'aplikasi'], true), 404);
        $maxKb = $side === 'aplikasi' ? 2048 : 1024;
        $r->validate(['logo' => "required|image|mimes:png,jpg,jpeg,webp|max:$maxKb"]);
        $name = "logo_{$side}_" . bin2hex(random_bytes(6)) . '.' . $r->file('logo')->getClientOriginalExtension();
        $r->file('logo')->move(public_path('uploads'), $name);
        $this->deleteLogo($side);
        Setting::put(["logo_$side" => $name]);
        return back()->with('ok', $side === 'aplikasi' ? 'Logo aplikasi disimpan.' : "Logo $side disimpan.");
    }

    public function removeLogo(string $side)
    {
        abort_unless(in_array($side, ['kiri', 'kanan', 'aplikasi'], true), 404);
        $this->deleteLogo($side);
        Setting::put(["logo_$side" => '']);
        return back()->with('ok', $side === 'aplikasi' ? 'Logo aplikasi dihapus.' : "Logo $side dihapus.");
    }

    private function deleteLogo(string $side): void
    {
        $old = Setting::map()["logo_$side"] ?? '';
        if ($old && is_file(public_path("uploads/$old"))) @unlink(public_path("uploads/$old"));
    }

    public function password(Request $r)
    {
        $r->validate(['lama' => 'required|string', 'baru' => 'required|string|min:8|confirmed']);
        $u = Auth::user();
        if (!Hash::check($r->input('lama'), $u->password)) {
            return back()->withErrors(['lama' => 'Password lama salah.']);
        }
        $u->password = Hash::make($r->input('baru'));
        $u->save();
        return back()->with('ok', 'Password diganti.');
    }

    public function adminName(Request $r)
    {
        $r->validate(['name' => 'required|string|max:255']);
        $user = Auth::user();
        $user->name = trim($r->input('name'));
        $user->save();

        return back()->with('ok', 'Nama admin berhasil diperbarui.');
    }
}
