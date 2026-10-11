import 'package:flutter/material.dart';
import 'package:provider/provider.dart';

import '../models/models.dart';
import '../state/app_state.dart';
import '../util_format.dart';
import 'kunjungan_screen.dart';

class RuteScreen extends StatelessWidget {
  const RuteScreen({super.key});

  @override
  Widget build(BuildContext context) {
    final s = context.watch<AppState>();
    final d = s.today;
    if (d == null) return const Center(child: CircularProgressIndicator());
    final next = d.berikutnya;

    return RefreshIndicator(
      onRefresh: s.refresh,
      child: ListView.separated(
        padding: const EdgeInsets.all(14),
        itemCount: d.outlets.length,
        separatorBuilder: (_, _) => const SizedBox(height: 8),
        itemBuilder: (context, i) {
          final o = d.outlets[i];
          return _OutletTile(outlet: o, isNext: next?.id == o.id, no: i + 1);
        },
      ),
    );
  }
}

class _OutletTile extends StatelessWidget {
  const _OutletTile({required this.outlet, required this.isNext, required this.no});
  final Outlet outlet;
  final bool isNext;
  final int no;

  @override
  Widget build(BuildContext context) {
    final t = Theme.of(context);
    final k = outlet.kunjungan;
    final (String label, Color color) = switch (k?.status) {
      'order' => (k!.orderTipe == 'canvas' ? 'Terjual' : 'Order ${k.orderStatus == 'menunggu_approval' ? '(approval)' : ''}', Colors.green),
      'tanpa_order' => ('Tanpa order', t.colorScheme.outline),
      'tutup' => ('Tutup', t.colorScheme.outline),
      'checkin' => ('Sedang dikunjungi', t.colorScheme.primary),
      _ => (isNext ? 'Berikutnya' : 'Belum', isNext ? t.colorScheme.primary : t.colorScheme.outline),
    };
    final sub = k?.orderNo != null ? '${k!.orderNo} · ${rupiah(k.orderTotal ?? 0)}' : '${outlet.tipe} · ${outlet.alamat}';

    return Card(
      shape: RoundedRectangleBorder(
        borderRadius: BorderRadius.circular(12),
        side: BorderSide(color: isNext ? t.colorScheme.primary : t.colorScheme.outlineVariant, width: isNext ? 1.5 : 1),
      ),
      child: ListTile(
        enabled: !outlet.selesai,
        onTap: () => Navigator.push(context, MaterialPageRoute(builder: (_) => KunjunganScreen(outlet: outlet))),
        leading: CircleAvatar(
          backgroundColor: outlet.selesai ? Colors.green : (isNext ? t.colorScheme.primary : t.colorScheme.surfaceContainerHighest),
          foregroundColor: outlet.selesai || isNext ? Colors.white : t.colorScheme.onSurface,
          child: outlet.selesai ? const Icon(Icons.check) : Text('$no'),
        ),
        title: Text(outlet.nama, style: const TextStyle(fontWeight: FontWeight.w600)),
        subtitle: Text(sub, maxLines: 1, overflow: TextOverflow.ellipsis),
        trailing: Text(label, style: TextStyle(color: color, fontWeight: FontWeight.w600, fontSize: 12)),
      ),
    );
  }
}
