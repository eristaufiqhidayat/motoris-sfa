<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Muat pagi: satu sales, satu kendaraan, satu hari. Ditutup dengan setoran.
        Schema::create('loadings', function (Blueprint $table) {
            $table->id();
            $table->string('no')->unique();
            $table->foreignId('user_id')->constrained();
            $table->foreignId('sales_route_id')->constrained();
            $table->foreignId('vehicle_location_id')->constrained('locations');
            $table->date('tanggal');
            $table->string('status')->default('jalan'); // jalan | setor | selesai
            $table->json('muat'); // {product_id: qty}
            $table->unsignedBigInteger('setor_tunai')->default(0);
            $table->timestamp('setor_at')->nullable();
            $table->timestamp('diterima_at')->nullable();
            $table->foreignId('diterima_oleh')->nullable()->constrained('users');
            $table->json('selisih')->nullable();
            $table->timestamps();
        });

        Schema::create('visits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained();
            $table->foreignId('outlet_id')->constrained();
            $table->foreignId('loading_id')->nullable()->constrained();
            $table->date('tanggal');
            $table->timestamp('checkin_at');
            $table->decimal('lat', 10, 7)->nullable();
            $table->decimal('lng', 10, 7)->nullable();
            $table->unsignedInteger('jarak_meter')->nullable();
            $table->string('status')->default('checkin'); // checkin | order | tanpa_order | tutup
            $table->json('stok_outlet')->nullable(); // {product_id: sisa}
            $table->string('catatan')->nullable();
            $table->timestamps();
        });

        // tipe canvas = jual langsung dari motor (motoris); taking_order = dikirim kemudian (SFA).
        Schema::create('sales_orders', function (Blueprint $table) {
            $table->id();
            $table->string('no')->unique();
            $table->string('tipe');
            $table->foreignId('outlet_id')->constrained();
            $table->foreignId('user_id')->constrained();
            $table->foreignId('visit_id')->nullable()->constrained();
            $table->foreignId('loading_id')->nullable()->constrained();
            $table->string('status'); // selesai | menunggu_approval | disetujui | ditolak
            $table->string('metode_bayar')->nullable(); // tunai | qris | tempo
            $table->unsignedInteger('galon_kosong_ditarik')->default(0);
            $table->unsignedBigInteger('subtotal')->default(0);
            $table->unsignedBigInteger('deposit')->default(0);
            $table->unsignedBigInteger('total')->default(0);
            $table->string('alasan')->nullable();
            $table->foreignId('approved_by')->nullable()->constrained('users');
            $table->timestamps();
        });

        Schema::create('sales_order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sales_order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained();
            $table->unsignedInteger('qty');
            $table->unsignedInteger('harga');
            $table->unsignedBigInteger('subtotal');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sales_order_items');
        Schema::dropIfExists('sales_orders');
        Schema::dropIfExists('visits');
        Schema::dropIfExists('loadings');
    }
};
