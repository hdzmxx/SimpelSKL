@extends('layouts.app')
@section('content')
@php $edit = $student->exists; $ab = $student->absensi ?? [0, 0, 0, 0, 0, 0]; @endphp
<h2 class="t">{{ $edit ? 'Edit' : 'Tambah' }} siswa</h2>
<form class="card" method="POST" action="{{ $edit ? route('siswa.update', $student) : route('siswa.store') }}">@csrf @if ($edit) @method('PUT') @endif
  <div class="grid2">
    <label class="f">Nama<input name="nama" value="{{ old('nama', $student->nama) }}" required></label>
    <label class="f">NISN<input name="nisn" value="{{ old('nisn', $student->nisn) }}" required></label>
    <label class="f">Jenis kelamin
      <select name="jenis_kelamin" required>
        <option value="">Pilih jenis kelamin</option>
        <option value="L" @selected(old('jenis_kelamin', $student->jenis_kelamin) === 'L')>Laki-laki</option>
        <option value="P" @selected(old('jenis_kelamin', $student->jenis_kelamin) === 'P')>Perempuan</option>
      </select>
    </label>
    <label class="f">NIS<input name="nis" value="{{ old('nis', $student->nis) }}"></label>
    <label class="f">Kelas<input name="kelas" value="{{ old('kelas', $student->kelas) }}" required></label>
    <label class="f">No. urut (menentukan nomor dokumen)<input type="number" name="no_urut" value="{{ old('no_urut', $student->no_urut) }}"></label>
    <label class="f">Tempat lahir<input name="tempat_lahir" value="{{ old('tempat_lahir', $student->tempat_lahir) }}"></label>
    <label class="f">Tanggal lahir<input type="date" name="tgl_lahir" value="{{ old('tgl_lahir', $student->tgl_lahir?->format('Y-m-d')) }}"></label>
    <label class="f">Nomor ijazah<input name="no_ijazah" value="{{ old('no_ijazah', $student->no_ijazah) }}"></label>
  </div>
  <h4>Ketidakhadiran</h4>
  <div class="attendance-groups">
    @foreach ([5 => 0, 6 => 3] as $semester => $offset)
      <section class="attendance-semester">
        <h5>Semester {{ $semester }}</h5>
        <div class="attendance-fields">
          @foreach (['Sakit', 'Izin', 'Alpa'] as $i => $label)
            @php $index = $offset + $i; @endphp
            <label class="f">{{ $label }}<input type="number" min="0" name="absensi[{{ $index }}]" value="{{ old("absensi.$index", $ab[$index] ?? 0) }}"></label>
          @endforeach
        </div>
      </section>
    @endforeach
  </div>
  <p style="color:var(--mut);font-size:13px">Nilai diisi lewat menu <b>Nilai → Input Nilai</b>, lalu pilih mata pelajaran di bagian atas halaman.</p>
  <button class="p">Simpan</button> <a class="btn" href="{{ route('siswa.index') }}">Batal</a>
</form>
@endsection
