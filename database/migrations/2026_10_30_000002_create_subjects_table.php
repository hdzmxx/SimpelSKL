<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('subjects', function (Blueprint $t) {
            $t->id();
            $t->string('kode', 20)->unique();
            $t->string('nama');
            $t->string('nama_rapor')->nullable();
            $t->unsignedInteger('urut')->default(0);
            $t->boolean('ada_praktik')->default(false);
            $t->decimal('bobot_semester', 5, 2)->default(60);
            $t->decimal('bobot_tertulis', 5, 2)->default(40);
            $t->decimal('bobot_praktik', 5, 2)->default(0);
            $t->boolean('ujian_dari_sem6')->default(false);
            $t->boolean('tampil_di_rapor')->default(false);
            $t->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('subjects');
    }
};
