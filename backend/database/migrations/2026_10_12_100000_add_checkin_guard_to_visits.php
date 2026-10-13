<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('visits', function (Blueprint $table) {
            $table->float('gps_akurasi')->nullable()->after('jarak_meter'); // meter, dari HP
            $table->string('foto_path')->nullable()->after('gps_akurasi');
            $table->json('flags')->nullable()->after('foto_path'); // kode kejanggalan untuk dicek supervisor
            $table->timestamp('diperiksa_at')->nullable()->after('flags');
            $table->foreignId('diperiksa_oleh')->nullable()->after('diperiksa_at')->constrained('users');
        });
    }

    public function down(): void
    {
        Schema::table('visits', function (Blueprint $table) {
            $table->dropConstrainedForeignId('diperiksa_oleh');
            $table->dropColumn(['gps_akurasi', 'foto_path', 'flags', 'diperiksa_at']);
        });
    }
};
