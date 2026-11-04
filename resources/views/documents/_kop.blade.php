@php
  $w = (float) ($s['logo_ukuran'] ?: 22);
  $L = $s['logo_kiri'] ? asset('uploads/' . $s['logo_kiri']) : null;
  $R = $s['logo_kanan'] ? asset('uploads/' . $s['logo_kanan']) : null;
@endphp
<div class="kop">
  @if ($L || $R)<div class="lg" style="width:{{ $w }}mm">@if ($L)<img src="{{ $L }}" alt="" style="width:{{ $w }}mm;height:{{ $w }}mm;object-fit:contain">@endif</div>@endif
  <div class="tx">
    <div>{{ $s['pemda'] }}</div><div>{{ $s['dinas'] }}</div>
    <div class="nm">{{ strtoupper($s['nama_sekolah']) }}</div>
    <div class="al">{{ $s['alamat'] }}<br>{{ $s['telp'] }}<br>{{ $s['email'] }}</div>
  </div>
  @if ($L || $R)<div class="lg" style="width:{{ $w }}mm">@if ($R)<img src="{{ $R }}" alt="" style="width:{{ $w }}mm;height:{{ $w }}mm;object-fit:contain">@endif</div>@endif
</div>
