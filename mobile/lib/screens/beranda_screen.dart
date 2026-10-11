import 'package:flutter/material.dart';
import 'package:provider/provider.dart';

import '../state/app_state.dart';
import '../util_format.dart';
import '../widgets/common.dart';
import 'kunjungan_screen.dart';

class BerandaScreen extends StatelessWidget {
  const BerandaScreen({super.key, required this.onBukaRute});
  final VoidCallback onBukaRute;

  @override
  Widget build(BuildContext context) {
    final s = context.watch<AppState>();
    final d = s.today;
    if (d == null) return const Center(child: CircularProgressIndicator());
    final t = Theme.of(context);
    final next = d.berikutnya;
    final galon = s.galon;

    return RefreshIndicator(
      onRefresh: s.refresh,
      child: ListView(padding: const EdgeInsets.all(14), children: [
        Text('Halo, ${s.user?['nama'] ?? ''}', style: t.textTheme.titleMedium),
        Text('${s.user?['kode'] ?? ''} · ${d.ruteKode} ${d.ruteNama ?? ''}', style: t.textTheme.bodySmall),
        const SizedBox(height: 12),
        if (d.muatStatus == null)
          const _Banner('Belum ada muat hari ini. Anda tetap bisa ambil order (dikirim kemudian), tetapi belum bisa jual langsung.')
        else if (d.muatStatus != 'jalan')
          _Banner(d.muatStatus == 'setor' ? 'Setoran terkirim, menunggu dicek gudang.' : 'Setoran sudah diterima gudang.'),
        Section(
          title: 'Progres rute',
          child: Column(children: [
            KV('Outlet dikunjungi', '${d.selesai} / ${d.outlets.length}', bold: true),
            const SizedBox(height: 6),
            LinearProgressIndicator(value: d.outlets.isEmpty ? 0 : d.selesai / d.outlets.length, minHeight: 6, borderRadius: BorderRadius.circular(6)),
          ]),
        ),
        const SizedBox(height: 10),
        Section(
          title: 'Stok di kendaraan',
          child: Column(children: [
            Row(children: [
              Stat(angka(galon == null ? 0 : d.stokIsi(galon.id)), 'galon isi'),
              const SizedBox(width: 8),
              Stat(angka(d.galonKosong), 'galon kosong'),
            ]),
            const SizedBox(height: 8),
            for (final l in d.stok.where((l) => l.kode != 'GLN19' && l.qty > 0)) KV(l.nama, '${l.qty}'),
          ]),
        ),
        const SizedBox(height: 10),
        Section(
          title: 'Uang hari ini',
          child: Column(children: [
            KV('Tunai di tangan', rupiah(d.uang['tunai'] ?? 0), bold: true),
            KV('QRIS', rupiah(d.uang['qris'] ?? 0)),
            KV('Tempo (piutang)', rupiah(d.uang['tempo'] ?? 0)),
          ]),
        ),
        const SizedBox(height: 16),
        if (next != null)
          FilledButton.icon(
            onPressed: () => Navigator.push(context, MaterialPageRoute(builder: (_) => KunjunganScreen(outlet: next))),
            icon: const Icon(Icons.storefront),
            label: Padding(padding: const EdgeInsets.all(12), child: Text('Kunjungi ${next.nama}')),
          )
        else
          OutlinedButton(onPressed: onBukaRute, child: const Text('Semua outlet sudah dikunjungi')),
      ]),
    );
  }
}

class _Banner extends StatelessWidget {
  const _Banner(this.text);
  final String text;

  @override
  Widget build(BuildContext context) => Padding(
        padding: const EdgeInsets.only(bottom: 10),
        child: Material(
          color: Theme.of(context).colorScheme.secondaryContainer,
          borderRadius: BorderRadius.circular(12),
          child: Padding(padding: const EdgeInsets.all(12), child: Text(text)),
        ),
      );
}
