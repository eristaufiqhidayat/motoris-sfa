import 'package:flutter/material.dart';
import 'package:geolocator/geolocator.dart';
import 'package:image_picker/image_picker.dart';
import 'package:provider/provider.dart';

import '../api/api_client.dart';
import '../models/models.dart';
import '../state/app_state.dart';
import '../util_format.dart';
import '../widgets/common.dart';

enum Mode { jualLangsung, ambilOrder }

/// Satu kunjungan outlet: check-in → cek stok toko → jual langsung (motoris) atau ambil order (SFA).
class KunjunganScreen extends StatefulWidget {
  const KunjunganScreen({super.key, required this.outlet});
  final Outlet outlet;

  @override
  State<KunjunganScreen> createState() => _KunjunganScreenState();
}

class _KunjunganScreenState extends State<KunjunganScreen> {
  VisitDetail? _visit;
  int _step = 0; // 0 check-in, 1 stok toko, 2 order
  bool _busy = false;
  final Map<int, int> _stokToko = {};
  final Map<int, int> _items = {};
  int _kosong = 0;
  String _bayar = 'tunai';
  Mode? _mode;

  Outlet get o => widget.outlet;

  @override
  void initState() {
    super.initState();
    // Kunjungan yang sudah check-in tapi belum ditutup bisa dilanjutkan.
    if (o.kunjungan?.open == true) _lanjutkan();
  }

  Future<void> _run(Future<void> Function() f) async {
    setState(() => _busy = true);
    try {
      await f();
    } catch (e) {
      if (mounted) showError(context, e);
    } finally {
      if (mounted) setState(() => _busy = false);
    }
  }

  Future<Position?> _posisi() async {
    try {
      if (!await Geolocator.isLocationServiceEnabled()) return null;
      var perm = await Geolocator.checkPermission();
      if (perm == LocationPermission.denied) perm = await Geolocator.requestPermission();
      if (perm == LocationPermission.denied || perm == LocationPermission.deniedForever) return null;
      return await Geolocator.getCurrentPosition(locationSettings: const LocationSettings(accuracy: LocationAccuracy.high, timeLimit: Duration(seconds: 15)));
    } catch (_) {
      return null;
    }
  }

  void _lanjutkan() => WidgetsBinding.instance.addPostFrameCallback((_) => _run(() async {
        final v = await context.read<AppState>().lanjutkanKunjungan(o);
        setState(() {
          _visit = v;
          _step = 1;
        });
      }));

  /// Urutan: GPS dulu (tolak cepat bila palsu/mati), lalu foto toko, lalu kirim ke server.
  Future<void> _checkin() => _run(() async {
        final app = context.read<AppState>();
        final pos = await _posisi();
        if (pos == null && o.lat != null) {
          throw ApiException('Lokasi HP tidak terbaca. Aktifkan GPS dan izinkan akses lokasi untuk Oasis Sales.');
        }
        if (pos?.isMocked == true) {
          throw ApiException('Lokasi palsu terdeteksi. Matikan aplikasi Fake GPS / mock location, lalu coba lagi.');
        }
        final foto = await ImagePicker().pickImage(source: ImageSource.camera, maxWidth: 1280, imageQuality: 70);
        if (foto == null) throw ApiException('Foto toko wajib diambil untuk check-in.');
        final v = await app.checkin(o,
            lat: pos?.latitude, lng: pos?.longitude, akurasi: pos?.accuracy, mock: pos?.isMocked ?? false, foto: await foto.readAsBytes());
        setState(() {
          _visit = v;
          _step = 1;
        });
      });

  Future<void> _simpanStok() => _run(() async {
        final app = context.read<AppState>();
        for (final p in app.products) {
          _stokToko.putIfAbsent(p.id, () => 0);
        }
        final v = await app.simpanStokOutlet(_visit!.id, _stokToko);
        setState(() {
          _visit = v;
          _pakaiSaran(app, jualLangsung: app.today?.bisaJualLangsung ?? false);
          _step = 2;
        });
      });

  /// Isi order dari saran. Untuk jual langsung, dibatasi stok yang ada di motor.
  void _pakaiSaran(AppState app, {required bool jualLangsung}) {
    _items
      ..clear()
      ..addAll(_visit!.saranOrder);
    if (jualLangsung) {
      _items.updateAll((id, qty) => qty.clamp(0, app.today!.stokIsi(id)));
    }
  }

  Future<void> _tutup(String status) => _run(() async {
        await context.read<AppState>().tutupKunjungan(_visit!.id, status);
        if (mounted) Navigator.pop(context);
      });

  Future<void> _kirim() => _run(() async {
        final s = context.read<AppState>();
        final res = _mode == Mode.jualLangsung
            ? await s.jualLangsung(visitId: _visit!.id, items: _items, galonKosong: _kosong, metodeBayar: _bayar)
            : await s.ambilOrder(visitId: _visit!.id, items: _items);
        if (!mounted) return;
        await showDialog(
          context: context,
          builder: (_) => AlertDialog(
            icon: Icon(res.status == 'menunggu_approval' ? Icons.hourglass_top : Icons.check_circle, size: 40),
            title: Text(res.no),
            content: Text(switch (res.status) {
              'selesai' => 'Serah terima tersimpan. Total ${rupiah(res.total)} (${_bayar.toUpperCase()}).',
              'menunggu_approval' => 'Order ${rupiah(res.total)} menunggu persetujuan supervisor: ${res.alasan ?? ''}.',
              _ => 'Order ${rupiah(res.total)} disetujui dan masuk antrean kirim.',
            }),
            actions: [TextButton(onPressed: () => Navigator.pop(context), child: const Text('OK'))],
          ),
        );
        if (mounted) Navigator.pop(context);
      });

  @override
  Widget build(BuildContext context) {
    final s = context.watch<AppState>();
    final t = Theme.of(context);
    return Scaffold(
      appBar: AppBar(title: Text(o.nama)),
      body: AbsorbPointer(
        absorbing: _busy,
        child: ListView(padding: const EdgeInsets.all(14), children: [
          Text('${o.tipe} · ${o.alamat}', style: t.textTheme.bodySmall),
          const SizedBox(height: 10),
          _Steps(step: _step),
          const SizedBox(height: 10),
          Section(
            title: 'Info outlet',
            child: Column(children: [
              KV('Galon dipinjam', '${o.galonDipinjam}'),
              if (o.bolehTempo) ...[
                KV('Piutang', rupiah(o.piutang), color: o.piutang > o.limitKredit * .85 ? t.colorScheme.error : null),
                KV('Limit kredit', rupiah(o.limitKredit)),
              ] else
                const KV('Pembayaran', 'Tunai / QRIS'),
            ]),
          ),
          const SizedBox(height: 10),
          if (_step == 0) ..._stepCheckin(),
          if (_step == 1) ..._stepStok(s),
          if (_step == 2) ..._stepOrder(s),
          if (_busy) const Padding(padding: EdgeInsets.all(12), child: Center(child: CircularProgressIndicator())),
        ]),
      ),
    );
  }

  List<Widget> _stepCheckin() => [
        FilledButton.icon(onPressed: _checkin, icon: const Icon(Icons.photo_camera), label: const Padding(padding: EdgeInsets.all(12), child: Text('Foto toko & check-in'))),
        const SizedBox(height: 6),
        const Text('Check-in memakai GPS (maks. 150 m dari outlet) dan foto tampak depan toko. Lokasi palsu akan ditolak.', textAlign: TextAlign.center),
      ];

  List<Widget> _stepStok(AppState s) => [
        if (_visit?.jarakMeter != null) Text('Check-in terverifikasi · ${_visit!.jarakMeter} m dari titik outlet', style: const TextStyle(color: Colors.green)),
        const SizedBox(height: 6),
        Section(
          title: 'Sisa stok di toko',
          child: Column(children: [
            for (final p in s.products)
              QtyStepper(label: p.nama, sub: p.satuan, value: _stokToko[p.id] ?? 0, onChanged: (v) => setState(() => _stokToko[p.id] = v)),
          ]),
        ),
        const SizedBox(height: 12),
        FilledButton(onPressed: _simpanStok, child: const Padding(padding: EdgeInsets.all(12), child: Text('Simpan & lihat saran order'))),
        const SizedBox(height: 8),
        Row(children: [
          Expanded(child: OutlinedButton(onPressed: () => _tutup('tutup'), child: const Text('Toko tutup'))),
          const SizedBox(width: 8),
          Expanded(child: OutlinedButton(onPressed: () => _tutup('tanpa_order'), child: const Text('Tanpa order'))),
        ]),
      ];

  List<Widget> _stepOrder(AppState s) {
    final d = s.today!;
    final galon = s.galon;
    final bisaJual = d.bisaJualLangsung;
    _mode ??= bisaJual ? Mode.jualLangsung : Mode.ambilOrder;
    final jual = _mode == Mode.jualLangsung;

    final subtotal = _items.entries.fold<int>(0, (a, e) => a + s.productById(e.key).harga * e.value);
    final galonQty = galon == null ? 0 : (_items[galon.id] ?? 0);
    final deposit = jual && galon != null ? (galonQty - _kosong).clamp(0, 1 << 30) * galon.deposit : 0;
    final total = subtotal + deposit;
    final lewatLimit = o.bolehTempo && o.piutang + total > o.limitKredit && (!jual || _bayar == 'tempo');
    final kurangStok = jual && _items.entries.any((e) => e.value > d.stokIsi(e.key));

    return [
      SegmentedButton<Mode>(
        segments: [
          ButtonSegment(value: Mode.jualLangsung, label: const Text('Jual langsung'), icon: const Icon(Icons.two_wheeler), enabled: bisaJual),
          const ButtonSegment(value: Mode.ambilOrder, label: Text('Ambil order'), icon: Icon(Icons.local_shipping)),
        ],
        selected: {_mode!},
        onSelectionChanged: (v) => setState(() {
          _mode = v.first;
          if (_mode == Mode.jualLangsung) _items.updateAll((id, qty) => qty.clamp(0, d.stokIsi(id)));
        }),
      ),
      const SizedBox(height: 6),
      Text(jual ? 'Barang diserahkan sekarang dari stok di motor.' : 'Barang dikirim kemudian dari gudang (truk).', style: Theme.of(context).textTheme.bodySmall),
      const SizedBox(height: 10),
      Section(
        title: 'Isi order',
        trailing: TextButton(onPressed: () => setState(() => _pakaiSaran(s, jualLangsung: jual)), child: const Text('Pakai saran')),
        child: Column(children: [
          for (final p in s.products)
            QtyStepper(
              label: p.nama,
              sub: '${rupiah(p.harga)} · ${jual ? 'di motor ${d.stokIsi(p.id)}' : 'saran ${_visit!.saranOrder[p.id] ?? 0}'}',
              value: _items[p.id] ?? 0,
              max: jual ? d.stokIsi(p.id) : null,
              step: p.returnable && !jual ? 5 : 1,
              onChanged: (v) => setState(() => _items[p.id] = v),
            ),
          if (jual && galon != null)
            QtyStepper(label: 'Galon kosong ditarik', sub: 'maks. ${o.galonDipinjam} milik toko', value: _kosong, max: o.galonDipinjam, onChanged: (v) => setState(() => _kosong = v)),
        ]),
      ),
      const SizedBox(height: 10),
      Section(
        title: 'Tagihan',
        child: Column(crossAxisAlignment: CrossAxisAlignment.stretch, children: [
          KV('Subtotal', rupiah(subtotal)),
          if (deposit > 0) KV('Deposit ${galonQty - _kosong} galon baru', rupiah(deposit)),
          const Divider(),
          KV('Total', rupiah(total), bold: true),
          if (jual) ...[
            const SizedBox(height: 8),
            Wrap(spacing: 8, children: [
              for (final m in ['tunai', 'qris', 'tempo'])
                ChoiceChip(
                  label: Text(m == 'qris' ? 'QRIS' : '${m[0].toUpperCase()}${m.substring(1)}'),
                  selected: _bayar == m,
                  onSelected: m == 'tempo' && !o.bolehTempo ? null : (_) => setState(() => _bayar = m),
                ),
            ]),
          ] else
            KV('Pembayaran', o.bolehTempo ? 'Tempo' : 'Tunai saat kirim'),
          if (lewatLimit)
            Padding(
              padding: const EdgeInsets.only(top: 8),
              child: Text(
                jual ? 'Melewati limit kredit. Pilih tunai atau QRIS.' : 'Melewati limit kredit. Order akan menunggu persetujuan supervisor.',
                style: TextStyle(color: Theme.of(context).colorScheme.error),
              ),
            ),
          if (kurangStok) Text('Stok di motor tidak cukup.', style: TextStyle(color: Theme.of(context).colorScheme.error)),
        ]),
      ),
      const SizedBox(height: 14),
      FilledButton(
        onPressed: subtotal == 0 || kurangStok || (jual && lewatLimit) ? null : _kirim,
        child: Padding(
          padding: const EdgeInsets.all(12),
          child: Text(jual ? 'Konfirmasi serah terima' : (lewatLimit ? 'Kirim untuk approval' : 'Kirim order')),
        ),
      ),
      const SizedBox(height: 8),
      OutlinedButton(onPressed: () => _tutup('tanpa_order'), child: const Text('Selesai tanpa order')),
    ];
  }
}

class _Steps extends StatelessWidget {
  const _Steps({required this.step});
  final int step;

  @override
  Widget build(BuildContext context) {
    const labels = ['Check-in', 'Stok toko', 'Order'];
    return Row(children: [
      for (var i = 0; i < labels.length; i++) ...[
        Expanded(
          child: Chip(
            label: Text('${i + 1}. ${labels[i]}', overflow: TextOverflow.ellipsis),
            backgroundColor: i == step ? Theme.of(context).colorScheme.primaryContainer : null,
            visualDensity: VisualDensity.compact,
          ),
        ),
        if (i < labels.length - 1) const SizedBox(width: 6),
      ],
    ]);
  }
}
