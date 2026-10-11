<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('depots', function (Blueprint $table) {
            $table->id();
            $table->string('kode')->unique();
            $table->string('nama');
            $table->string('alamat')->nullable();
            $table->timestamps();
        });

        Schema::table('users', function (Blueprint $table) {
            // admin = admin depo / supervisor, gudang = staf gudang, sales = motoris + SFA
            $table->string('role')->default('sales')->after('password');
            $table->string('kode')->nullable()->after('role');
            $table->foreignId('depot_id')->nullable()->after('kode')->constrained();
        });

        // Lokasi stok: gudang depo, karantina, atau kendaraan (motor/truk).
        Schema::create('locations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('depot_id')->constrained();
            $table->string('tipe'); // gudang | karantina | kendaraan
            $table->string('kode')->unique();
            $table->string('nama');
            $table->timestamps();
        });

        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->string('kode')->unique();
            $table->string('nama');
            $table->string('satuan');
            $table->unsignedInteger('harga');
            $table->unsignedInteger('deposit')->default(0); // hanya untuk galon guna ulang
            $table->boolean('returnable')->default(false);
            $table->unsignedInteger('min_stok')->default(0);
            $table->unsignedInteger('max_stok')->default(0);
            $table->timestamps();
        });

        Schema::create('sales_routes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('depot_id')->constrained();
            $table->string('kode')->unique();
            $table->string('nama');
            $table->foreignId('user_id')->nullable()->constrained();
            $table->foreignId('vehicle_location_id')->nullable()->constrained('locations');
            $table->timestamps();
        });

        Schema::create('outlets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('depot_id')->constrained();
            $table->foreignId('sales_route_id')->nullable()->constrained();
            $table->unsignedSmallInteger('urutan')->default(0);
            $table->string('kode')->unique();
            $table->string('nama');
            $table->string('tipe'); // agen | grosir | modern | warung | depot | kantor
            $table->string('alamat');
            $table->decimal('lat', 10, 7)->nullable();
            $table->decimal('lng', 10, 7)->nullable();
            $table->unsignedBigInteger('limit_kredit')->default(0);
            $table->unsignedBigInteger('piutang')->default(0);
            $table->unsignedInteger('galon_dipinjam')->default(0);
            $table->json('rata_jual_mingguan')->nullable(); // {product_kode: qty}
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('outlets');
        Schema::dropIfExists('sales_routes');
        Schema::dropIfExists('products');
        Schema::dropIfExists('locations');
        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('depot_id');
            $table->dropColumn(['role', 'kode']);
        });
        Schema::dropIfExists('depots');
    }
};
