<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Saldo stok per lokasi, produk, dan kondisi (galon: isi / kosong).
        Schema::create('stock_balances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('location_id')->constrained();
            $table->foreignId('product_id')->constrained();
            $table->string('kondisi')->default('isi');
            $table->integer('qty')->default(0);
            $table->timestamps();
            $table->unique(['location_id', 'product_id', 'kondisi']);
        });

        // Kartu stok: setiap perpindahan tercatat. from/to null = keluar/masuk dari luar depo.
        Schema::create('stock_movements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained();
            $table->string('kondisi')->default('isi');
            $table->foreignId('from_location_id')->nullable()->constrained('locations');
            $table->foreignId('to_location_id')->nullable()->constrained('locations');
            $table->unsignedInteger('qty');
            $table->string('jenis'); // terima_pabrik | kirim_pabrik | muat | setoran | jual | tarik_kosong | opname | karantina
            $table->string('ref_no')->nullable();
            $table->string('keterangan')->nullable();
            $table->foreignId('user_id')->nullable()->constrained();
            $table->timestamps();
            $table->index(['created_at']);
        });

        Schema::create('galons', function (Blueprint $table) {
            $table->id();
            $table->string('serial')->unique();
            $table->unsignedSmallInteger('siklus')->default(0);
            $table->date('tanggal_produksi')->nullable();
            $table->string('kondisi')->default('isi'); // isi | kosong | rusak | pensiun
            $table->foreignId('location_id')->nullable()->constrained();
            $table->foreignId('outlet_id')->nullable()->constrained();
            $table->timestamp('last_moved_at')->nullable();
            $table->timestamps();
        });

        Schema::create('galon_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('galon_id')->constrained()->cascadeOnDelete();
            $table->string('event');
            $table->string('dari')->nullable();
            $table->string('ke')->nullable();
            $table->string('ref_no')->nullable();
            $table->timestamps();
        });

        Schema::create('goods_receipts', function (Blueprint $table) {
            $table->id();
            $table->string('no')->unique();
            $table->foreignId('depot_id')->constrained();
            $table->string('sumber');
            $table->string('batch')->nullable();
            $table->foreignId('product_id')->constrained();
            $table->unsignedInteger('qty_kirim');
            $table->unsignedInteger('qty_terima')->default(0);
            $table->unsignedInteger('qty_tolak')->default(0);
            $table->string('status')->default('transit'); // transit | diterima
            $table->timestamp('diterima_at')->nullable();
            $table->foreignId('user_id')->nullable()->constrained();
            $table->timestamps();
        });

        Schema::create('stock_opnames', function (Blueprint $table) {
            $table->id();
            $table->string('no')->unique();
            $table->foreignId('location_id')->constrained();
            $table->string('status')->default('draft'); // draft | posted
            $table->timestamp('posted_at')->nullable();
            $table->foreignId('user_id')->nullable()->constrained();
            $table->timestamps();
        });

        Schema::create('stock_opname_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('stock_opname_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained();
            $table->string('kondisi')->default('isi');
            $table->integer('qty_sistem');
            $table->integer('qty_fisik');
            $table->string('penyebab')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_opname_items');
        Schema::dropIfExists('stock_opnames');
        Schema::dropIfExists('goods_receipts');
        Schema::dropIfExists('galon_events');
        Schema::dropIfExists('galons');
        Schema::dropIfExists('stock_movements');
        Schema::dropIfExists('stock_balances');
    }
};
