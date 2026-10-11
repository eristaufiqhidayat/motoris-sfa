import 'package:flutter_test/flutter_test.dart';
import 'package:oasis_sales/models/models.dart';

void main() {
  test('Today menghitung stok motor dan outlet berikutnya', () {
    final today = Today.fromJson({
      'rute': {'kode': 'R-12', 'nama': 'Bekasi Timur'},
      'muat': {'no': 'MT-2610-0001', 'status': 'jalan'},
      'stok_kendaraan': [
        {'product_id': 1, 'kode': 'GLN19', 'nama': 'Galon 19 L', 'kondisi': 'isi', 'qty': 25},
        {'product_id': 1, 'kode': 'GLN19', 'nama': 'Galon 19 L', 'kondisi': 'kosong', 'qty': 3},
      ],
      'uang': {'tunai': 100000, 'qris': 0, 'tempo': 0},
      'outlets': [
        {'id': 1, 'kode': 'OT-1', 'nama': 'A', 'tipe': 'warung', 'alamat': 'x', 'urutan': 1, 'limit_kredit': 0, 'piutang': 0, 'galon_dipinjam': 2, 'boleh_tempo': false,
         'kunjungan': {'id': 9, 'status': 'order', 'order': {'no': 'DO-1', 'tipe': 'canvas', 'total': 40000, 'status': 'selesai'}}},
        {'id': 2, 'kode': 'OT-2', 'nama': 'B', 'tipe': 'agen', 'alamat': 'y', 'urutan': 2, 'limit_kredit': '6000000', 'piutang': 5400000, 'galon_dipinjam': 30, 'boleh_tempo': true, 'kunjungan': null},
      ],
    });

    expect(today.bisaJualLangsung, isTrue);
    expect(today.stokIsi(1), 25);
    expect(today.galonKosong, 3);
    expect(today.selesai, 1);
    expect(today.berikutnya?.nama, 'B');
    expect(today.outlets[1].sisaLimit, 600000);
  });
}
