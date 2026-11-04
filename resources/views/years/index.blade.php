@extends('layouts.app')
@section('content')
<h2 class="t">Tahun Pelajaran</h2>
<p style="color:var(--mut);margin-top:-6px">Tiap tahun pelajaran punya siswa, mapel dengan bobotnya, dan pengaturan kelulusan sendiri. Data tahun lama tetap tersimpan dan tidak berubah ketika Anda mengubah bobot di tahun baru.</p>
<div class="tw" style="max-width:820px;margin-bottom:16px"><table class="t">
  <thead><tr><th>Tahun pelajaran</th><th class="n">Siswa</th><th class="n">Mapel</th><th>Status</th><th>Aksi</th></tr></thead>
  <tbody>
  @foreach ($years as $y)
    <tr><td><b>{{ $y->nama }}</b></td><td class="n">{{ $y->jumlahSiswa() }}</td><td class="n">{{ $y->jumlahMapel() }}</td>
      <td>{{ $y->aktif ? 'Aktif (dibuka saat login)' : '' }}{{ $cur && $cur->id === $y->id ? ' · sedang dipilih' : '' }}</td>
      <td class="nw">
        @if (!$cur || $cur->id !== $y->id)
          <form class="inline" method="POST" action="{{ route('tahun.pilih') }}">@csrf<input type="hidden" name="id" value="{{ $y->id }}"><button>Pilih</button></form>
        @endif
        @if (!$y->aktif)
          <form class="inline" method="POST" action="{{ route('tahun.aktif', $y) }}">@csrf<button>Jadikan aktif</button></form>
          <form class="inline" method="POST" action="{{ route('tahun.hapus', $y) }}" onsubmit="return confirm('Hapus tahun pelajaran {{ $y->nama }}? Hanya bisa bila tidak ada siswa.')">@csrf @method('DELETE')<button class="d">Hapus</button></form>
        @endif
      </td></tr>
  @endforeach
  </tbody></table></div>
<form class="card" method="POST" action="{{ route('tahun.store') }}" style="max-width:820px">@csrf
  <h3 style="margin-top:0">Tambah tahun pelajaran</h3>
  <div class="grid2">
    <label class="f">Tahun pelajaran (mis. 2026/2027)<input name="nama" value="{{ old('nama') }}" placeholder="2026/2027" required pattern="\d{4}/\d{4}"></label>
    <label class="f">Salin mapel &amp; bobot dari<select name="salin_dari"><option value="">Jangan salin (mulai kosong)</option>@foreach ($years as $y)<option value="{{ $y->id }}" @selected($cur && $cur->id === $y->id)>{{ $y->nama }}</option>@endforeach</select></label>
  </div>
  <p style="color:var(--mut);font-size:13px">Setelah dibuat: ubah bobot di <b>Mapel &amp; Bobot</b> bila perlu, isi nomor SK, tanggal, dan penomoran di <b>Cetak Dokumen → Pengaturan dokumen</b>, lalu impor siswa di <b>Data Siswa</b>.
    Pengaturan yang ikut disalin hanya nama kepala sekolah, NIP, dan format nomor dokumen; tanggal dan nomor SK dikosongkan.</p>
  <button class="p">Buat tahun pelajaran</button>
</form>
@endsection
