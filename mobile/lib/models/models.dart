int _i(dynamic v) => v is int ? v : int.tryParse('$v') ?? 0;
double? _d(dynamic v) => v == null ? null : (v is num ? v.toDouble() : double.tryParse('$v'));

class Product {
  Product({required this.id, required this.kode, required this.nama, required this.satuan, required this.harga, required this.deposit, required this.returnable});

  factory Product.fromJson(Map<String, dynamic> j) => Product(
        id: _i(j['id']),
        kode: j['kode'],
        nama: j['nama'],
        satuan: j['satuan'],
        harga: _i(j['harga']),
        deposit: _i(j['deposit']),
        returnable: j['returnable'] == true,
      );

  final int id;
  final String kode;
  final String nama;
  final String satuan;
  final int harga;
  final int deposit;
  final bool returnable;
}

class VisitInfo {
  VisitInfo({required this.id, required this.status, this.orderNo, this.orderTipe, this.orderTotal, this.orderStatus});

  factory VisitInfo.fromJson(Map<String, dynamic> j) {
    final o = j['order'] as Map<String, dynamic>?;
    return VisitInfo(
      id: _i(j['id']),
      status: j['status'],
      orderNo: o?['no'],
      orderTipe: o?['tipe'],
      orderTotal: o == null ? null : _i(o['total']),
      orderStatus: o?['status'],
    );
  }

  final int id;
  final String status; // checkin | order | tanpa_order | tutup
  final String? orderNo;
  final String? orderTipe;
  final int? orderTotal;
  final String? orderStatus;

  bool get open => status == 'checkin';
}

class Outlet {
  Outlet({
    required this.id,
    required this.kode,
    required this.nama,
    required this.tipe,
    required this.alamat,
    required this.urutan,
    required this.limitKredit,
    required this.piutang,
    required this.galonDipinjam,
    required this.bolehTempo,
    this.lat,
    this.lng,
    this.kunjungan,
  });

  factory Outlet.fromJson(Map<String, dynamic> j) => Outlet(
        id: _i(j['id']),
        kode: j['kode'],
        nama: j['nama'],
        tipe: j['tipe'],
        alamat: j['alamat'],
        urutan: _i(j['urutan']),
        limitKredit: _i(j['limit_kredit']),
        piutang: _i(j['piutang']),
        galonDipinjam: _i(j['galon_dipinjam']),
        bolehTempo: j['boleh_tempo'] == true,
        lat: _d(j['lat']),
        lng: _d(j['lng']),
        kunjungan: j['kunjungan'] == null ? null : VisitInfo.fromJson(j['kunjungan']),
      );

  final int id;
  final String kode;
  final String nama;
  final String tipe;
  final String alamat;
  final int urutan;
  final int limitKredit;
  final int piutang;
  final int galonDipinjam;
  final bool bolehTempo;
  final double? lat;
  final double? lng;
  final VisitInfo? kunjungan;

  int get sisaLimit => (limitKredit - piutang).clamp(0, limitKredit);
  bool get selesai => kunjungan != null && !kunjungan!.open;
}

class StockLine {
  StockLine({required this.productId, required this.kode, required this.nama, required this.kondisi, required this.qty});

  factory StockLine.fromJson(Map<String, dynamic> j) =>
      StockLine(productId: _i(j['product_id']), kode: j['kode'] ?? '', nama: j['nama'], kondisi: j['kondisi'] ?? 'isi', qty: _i(j['qty']));

  final int productId;
  final String kode;
  final String nama;
  final String kondisi;
  final int qty;
}

class Today {
  Today({required this.ruteKode, required this.ruteNama, required this.muatStatus, required this.muatNo, required this.stok, required this.uang, required this.outlets});

  factory Today.fromJson(Map<String, dynamic> j) {
    final uang = (j['uang'] as Map).map((k, v) => MapEntry(k.toString(), _i(v)));
    return Today(
      ruteKode: j['rute']?['kode'],
      ruteNama: j['rute']?['nama'],
      muatStatus: j['muat']?['status'],
      muatNo: j['muat']?['no'],
      stok: (j['stok_kendaraan'] as List).map((e) => StockLine.fromJson(e)).toList(),
      uang: uang,
      outlets: (j['outlets'] as List).map((e) => Outlet.fromJson(e)).toList(),
    );
  }

  final String? ruteKode;
  final String? ruteNama;
  final String? muatStatus; // null = belum muat, jalan | setor | selesai
  final String? muatNo;
  final List<StockLine> stok;
  final Map<String, int> uang;
  final List<Outlet> outlets;

  bool get bisaJualLangsung => muatStatus == 'jalan';
  int stokIsi(int productId) => stok.where((s) => s.productId == productId && s.kondisi == 'isi').fold(0, (a, s) => a + s.qty);
  int get galonKosong => stok.where((s) => s.kondisi == 'kosong').fold(0, (a, s) => a + s.qty);
  int get selesai => outlets.where((o) => o.selesai).length;
  Outlet? get berikutnya => outlets.where((o) => !o.selesai).firstOrNull;
}

class VisitDetail {
  VisitDetail({required this.id, required this.status, required this.jarakMeter, required this.saranOrder});

  factory VisitDetail.fromJson(Map<String, dynamic> j) => VisitDetail(
        id: _i(j['id']),
        status: j['status'],
        jarakMeter: j['jarak_meter'] == null ? null : _i(j['jarak_meter']),
        saranOrder: {for (final s in (j['saran_order'] as List)) _i(s['product_id']): _i(s['qty'])},
      );

  final int id;
  final String status;
  final int? jarakMeter;
  final Map<int, int> saranOrder; // product_id -> qty
}

class OrderResult {
  OrderResult({required this.no, required this.tipe, required this.status, required this.total, required this.deposit, this.alasan});

  factory OrderResult.fromJson(Map<String, dynamic> j) =>
      OrderResult(no: j['no'], tipe: j['tipe'], status: j['status'], total: _i(j['total']), deposit: _i(j['deposit']), alasan: j['alasan']);

  final String no;
  final String tipe;
  final String status;
  final int total;
  final int deposit;
  final String? alasan;
}

class Settlement {
  Settlement({required this.no, required this.status, required this.sisa, required this.uang, required this.selisih});

  factory Settlement.fromJson(Map<String, dynamic> j) => Settlement(
        no: j['no'],
        status: j['status'],
        sisa: (j['sisa_di_kendaraan'] as List).map((e) => StockLine.fromJson({...e, 'product_id': 0})).toList(),
        uang: (j['uang'] as Map).map((k, v) => MapEntry(k.toString(), _i(v))),
        selisih: List<Map<String, dynamic>>.from(j['selisih'] ?? const []),
      );

  final String no;
  final String status;
  final List<StockLine> sisa;
  final Map<String, int> uang;
  final List<Map<String, dynamic>> selisih;
}
