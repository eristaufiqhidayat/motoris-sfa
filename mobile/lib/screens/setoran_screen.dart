import 'package:flutter/material.dart';
import 'package:provider/provider.dart';

import '../api/api_client.dart';
import '../models/models.dart';
import '../state/app_state.dart';
import '../util_format.dart';
import '../widgets/common.dart';

class SetoranScreen extends StatefulWidget {
  const SetoranScreen({super.key});

  @override
  State<SetoranScreen> createState() => _SetoranScreenState();
}

class _SetoranScreenState extends State<SetoranScreen> {
  Future<Settlement>? _future;
  bool _busy = false;

  @override
  void initState() {
    super.initState();
    _future = context.read<AppState>().setoran();
  }

  Future<void> _kirim() async {
    final ok = await showDialog<bool>(
      context: context,
      builder: (_) => AlertDialog(
        title: const Text('Kirim setoran?'),
        content: const Text('Setelah dikirim, Anda tidak bisa jual langsung lagi hari ini. Ambil order tetap bisa.'),
        actions: [
          TextButton(onPressed: () => Navigator.pop(context, false), child: const Text('Batal')),
          FilledButton(onPressed: () => Navigator.pop(context, true), child: const Text('Kirim')),
        ],
      ),
    );
    if (ok != true || !mounted) return;
    setState(() => _busy = true);
    try {
      final s = await context.read<AppState>().kirimSetoran();
      setState(() => _future = Future.value(s));
      if (mounted) showInfo(context, 'Setoran terkirim. Serahkan barang dan uang tunai ke gudang.');
    } catch (e) {
      if (mounted) showError(context, e);
    } finally {
      if (mounted) setState(() => _busy = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    return FutureBuilder<Settlement>(
      future: _future,
      builder: (context, snap) {
        if (snap.hasError) {
          final e = snap.error;
          return Center(child: Padding(padding: const EdgeInsets.all(24), child: Text(e is ApiException ? e.message : '$e', textAlign: TextAlign.center)));
        }
        if (!snap.hasData) return const Center(child: CircularProgressIndicator());
        final s = snap.data!;
        final total = s.uang.values.fold<int>(0, (a, b) => a + b);
        return ListView(padding: const EdgeInsets.all(14), children: [
          Section(
            title: 'Sisa di kendaraan · ${s.no}',
            child: Column(children: [
              for (final l in s.sisa.where((l) => l.qty > 0)) KV('${l.nama}${l.kondisi == 'kosong' ? ' (kosong)' : ''}', angka(l.qty)),
              if (s.sisa.every((l) => l.qty == 0)) const KV('Kendaraan', 'kosong'),
            ]),
          ),
          const SizedBox(height: 10),
          Section(
            title: 'Uang',
            child: Column(children: [
              KV('Tunai disetor ke kasir', rupiah(s.uang['tunai'] ?? 0), bold: true),
              KV('QRIS (sudah masuk)', rupiah(s.uang['qris'] ?? 0)),
              KV('Tempo (nota piutang)', rupiah(s.uang['tempo'] ?? 0)),
              const Divider(),
              KV('Total penjualan', rupiah(total), bold: true),
            ]),
          ),
          const SizedBox(height: 14),
          switch (s.status) {
            'jalan' => FilledButton(onPressed: _busy ? null : _kirim, child: const Padding(padding: EdgeInsets.all(12), child: Text('Kirim setoran ke gudang'))),
            'setor' => const Card(child: ListTile(leading: Icon(Icons.hourglass_top), title: Text('Menunggu cek gudang'), subtitle: Text('Admin gudang menghitung fisik sisa barang.'))),
            _ => Card(
                child: ListTile(
                  leading: const Icon(Icons.verified, color: Colors.green),
                  title: const Text('Setoran diterima gudang'),
                  subtitle: Text(s.selisih.isEmpty ? 'Fisik cocok dengan sistem.' : 'Ada selisih: ${s.selisih.map((x) => '${x['produk']} ${x['sistem']}→${x['fisik']}').join(', ')}'),
                ),
              ),
          },
        ]);
      },
    );
  }
}
