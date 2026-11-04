<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('students', function (Blueprint $t) {
            $t->id();
            $t->unsignedInteger('no_urut')->default(0)->index();
            $t->string('nisn', 20)->unique();
            $t->string('nis', 30)->nullable();
            $t->string('nama');
            $t->string('kelas', 20)->index();
            $t->string('tempat_lahir')->nullable();
            $t->date('tgl_lahir')->nullable();
            $t->string('no_ijazah', 40)->nullable();
            $t->json('absensi')->nullable();
            $t->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('students');
    }
};
