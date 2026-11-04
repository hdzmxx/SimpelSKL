<?php

namespace App\Http\Controllers;

use App\Models\Subject;
use Illuminate\Http\Request;
use App\Support\KolomCetak;
use App\Support\Year;
use Illuminate\Validation\Rule;

class SubjectController extends Controller
{
    public function index()
    {
        $aktif = Subject::aktif()->orderBy('urut')->get();
        $orders = [];
        foreach (range(1, 6) as $n) $orders[$n] = KolomCetak::urutan($aktif, $n);
        return view('subjects.index', ['subjects' => Subject::orderBy('urut')->get(), 'aktif' => $aktif, 'orders' => $orders]);
    }

    public function create()
    {
        return view('subjects.form', ['subject' => new Subject(['urut' => (int) Subject::max('urut') + 1, 'bobot_semester' => 60, 'bobot_tertulis' => 40, 'bobot_praktik' => 0, 'aktif' => true])]);
    }

    public function store(Request $r)
    {
        $d = $this->data($r);
        if (!is_array($d)) return $d;
        Subject::create($d);
        return redirect()->route('mapel.index')->with('ok', 'Mapel ditambahkan.');
    }

    public function edit(Subject $subject)
    {
        return view('subjects.form', ['subject' => $subject]);
    }

    public function update(Request $r, Subject $subject)
    {
        $d = $this->data($r, $subject);
        if (!is_array($d)) return $d;
        $subject->update($d);
        return redirect()->route('mapel.index')->with('ok', 'Mapel diperbarui.');
    }

    /** Geser mapel satu posisi (naik = ke kiri/atas, turun = ke kanan/bawah). Urutan dipakai di semua tabel dan file ekspor. */
    public function move(Subject $subject, string $arah)
    {
        $list = Subject::orderBy('urut')->orderBy('id')->get()->values();
        foreach ($list as $i => $m) {
            if ((int) $m->urut !== $i + 1) {
                $m->urut = $i + 1;
                $m->save();
            }
        }
        $i = $list->search(fn ($m) => $m->id === $subject->id);
        $j = $i;
        // lompati mapel nonaktif: nomor urutan hanya dihitung untuk mapel aktif
        do {
            $j += $arah === 'naik' ? -1 : 1;
        } while (isset($list[$j]) && !$list[$j]->aktif);
        if ($i !== false && $subject->aktif && isset($list[$j])) {
            [$a, $b] = [$list[$i]->urut, $list[$j]->urut];
            $list[$i]->urut = $b;
            $list[$j]->urut = $a;
            $list[$i]->save();
            $list[$j]->save();
        }
        return redirect()->route('mapel.index');
    }


    /** Aktifkan / nonaktifkan mapel untuk tahun pelajaran yang dipilih (data nilainya tidak berubah). */
    public function toggle(Subject $subject)
    {
        $subject->update(['aktif' => !$subject->aktif]);
        return redirect()->route('mapel.index')->with('ok', "Mapel {$subject->kode} " . ($subject->aktif ? 'diaktifkan' : 'dinonaktifkan') . ' untuk tahun pelajaran ini.');
    }

    /** Terapkan pembobotan global ke semua mapel tahun ini. */
    public function applyBobot(Request $r)
    {
        $d = $r->validate([
            'bobot_semester' => 'required|numeric|between:0,100',
            'bobot_tertulis' => 'required|numeric|between:0,100',
            'bobot_tertulis_praktik' => 'required|numeric|between:0,100',
            'bobot_praktik' => 'required|numeric|between:0,100',
        ]);
        $sem = (float) $d['bobot_semester'];
        $tNo = (float) $d['bobot_tertulis'];
        $tYes = (float) $d['bobot_tertulis_praktik'];
        $pYes = (float) $d['bobot_praktik'];

        if (abs($sem + $tNo - 100) > 0.001) {
            return back()->withErrors(['bobot_semester' => "Tanpa praktik: semester + tertulis harus 100% (sekarang " . ($sem + $tNo) . "%)."]);
        }
        if (abs($sem + $tYes + $pYes - 100) > 0.001) {
            return back()->withErrors(['bobot_praktik' => "Dengan praktik: semester + tertulis + praktik harus 100% (sekarang " . ($sem + $tYes + $pYes) . "%)."]);
        }

        $n = 0;
        foreach (Subject::orderBy('urut')->get() as $m) {
            if ($m->ada_praktik) {
                $m->update(['bobot_semester' => $sem, 'bobot_tertulis' => $tYes, 'bobot_praktik' => $pYes]);
            } else {
                $m->update(['bobot_semester' => $sem, 'bobot_tertulis' => $tNo, 'bobot_praktik' => 0]);
            }
            $n++;
        }
        return redirect()->route('mapel.index')->with('ok', "Pembobotan diterapkan ke $n mapel.");
    }

    public function destroy(Subject $subject)
    {
        $subject->delete();
        return redirect()->route('mapel.index')->with('ok', 'Mapel dan seluruh nilainya dihapus.');
    }

    /** Mengembalikan array data, atau response redirect bila total bobot tidak 100%. */
    private function data(Request $r, ?Subject $m = null)
    {
        $d = $r->validate([
            'kode' => ['required', 'string', 'max:20', Rule::unique('subjects', 'kode')->where('academic_year_id', Year::id())->ignore($m?->id)],
            'nama' => 'required|string|max:255',
            'nama_rapor' => 'nullable|string|max:255',
            'urut' => 'nullable|integer|min:0',
        ]);
        $d['ada_praktik'] = $r->boolean('ada_praktik');
        $d['ujian_dari_sem6'] = false;
        $d['tampil_di_rapor'] = $r->boolean('tampil_di_rapor');
        $d['aktif'] = $r->boolean('aktif');
        $d['urut'] = $d['urut'] ?? (int) Subject::max('urut') + 1;

        // Ambil bobot dari mapel sejenis yang sudah ada (global), atau bawaan
        $ref = Subject::where('ada_praktik', $d['ada_praktik'])->orderBy('urut')->first();
        if ($ref) {
            $d['bobot_semester'] = $ref->bobot_semester;
            $d['bobot_tertulis'] = $ref->bobot_tertulis;
            $d['bobot_praktik'] = $d['ada_praktik'] ? $ref->bobot_praktik : 0;
        } else {
            $d['bobot_semester'] = 60;
            $d['bobot_tertulis'] = $d['ada_praktik'] ? 12 : 40;
            $d['bobot_praktik'] = $d['ada_praktik'] ? 28 : 0;
        }
        return $d;
    }
}
