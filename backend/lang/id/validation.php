<?php

// Pesan validasi bahasa Indonesia (ringkas). Tambah kunci lain bila perlu.
return [
    'array' => ':attribute harus berupa daftar.',
    'email' => ':attribute harus alamat email yang valid.',
    'exists' => ':attribute tidak ditemukan.',
    'in' => ':attribute tidak valid.',
    'integer' => ':attribute harus bilangan bulat.',
    'max' => [
        'numeric' => ':attribute maksimal :max.',
        'string' => ':attribute maksimal :max karakter.',
    ],
    'min' => [
        'array' => ':attribute minimal berisi :min item.',
        'numeric' => ':attribute minimal :min.',
        'string' => ':attribute minimal :min karakter.',
    ],
    'numeric' => ':attribute harus berupa angka.',
    'present' => ':attribute wajib dikirim.',
    'required' => ':attribute wajib diisi.',
    'string' => ':attribute harus berupa teks.',

    'attributes' => [
        'email' => 'Email',
        'password' => 'Kata sandi',
        'outlet_id' => 'Outlet',
        'visit_id' => 'Kunjungan',
        'items' => 'Isi order',
        'galon_kosong' => 'Galon kosong',
        'metode_bayar' => 'Metode bayar',
        'qty' => 'Jumlah',
        'qty_tolak' => 'Jumlah ditolak',
        'stok' => 'Stok toko',
        'alasan' => 'Alasan',
    ],
];
