import 'package:flutter/material.dart';

class Section extends StatelessWidget {
  const Section({super.key, required this.title, required this.child, this.trailing});
  final String title;
  final Widget child;
  final Widget? trailing;

  @override
  Widget build(BuildContext context) {
    final t = Theme.of(context);
    return Card(
      child: Padding(
        padding: const EdgeInsets.all(14),
        child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
          Row(children: [
            Expanded(child: Text(title.toUpperCase(), style: t.textTheme.labelSmall?.copyWith(letterSpacing: .8, color: t.colorScheme.onSurfaceVariant))),
            ?trailing,
          ]),
          const SizedBox(height: 8),
          child,
        ]),
      ),
    );
  }
}

class KV extends StatelessWidget {
  const KV(this.label, this.value, {super.key, this.bold = false, this.color});
  final String label;
  final String value;
  final bool bold;
  final Color? color;

  @override
  Widget build(BuildContext context) {
    final style = TextStyle(fontWeight: bold ? FontWeight.w700 : FontWeight.w500, fontSize: bold ? 16 : 14, color: color);
    return Padding(
      padding: const EdgeInsets.symmetric(vertical: 3),
      child: Row(children: [
        Expanded(child: Text(label, style: TextStyle(color: Theme.of(context).colorScheme.onSurfaceVariant))),
        Text(value, style: style),
      ]),
    );
  }
}

class Stat extends StatelessWidget {
  const Stat(this.value, this.label, {super.key});
  final String value;
  final String label;

  @override
  Widget build(BuildContext context) {
    final t = Theme.of(context);
    return Expanded(
      child: Container(
        padding: const EdgeInsets.all(10),
        decoration: BoxDecoration(color: t.colorScheme.surfaceContainerHighest, borderRadius: BorderRadius.circular(10)),
        child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
          Text(value, style: t.textTheme.titleLarge?.copyWith(fontWeight: FontWeight.w800)),
          Text(label, style: t.textTheme.bodySmall),
        ]),
      ),
    );
  }
}

class QtyStepper extends StatelessWidget {
  const QtyStepper({super.key, required this.label, required this.value, required this.onChanged, this.sub, this.max, this.step = 1});
  final String label;
  final String? sub;
  final int value;
  final int? max;
  final int step;
  final ValueChanged<int> onChanged;

  @override
  Widget build(BuildContext context) {
    final t = Theme.of(context);
    return Padding(
      padding: const EdgeInsets.symmetric(vertical: 6),
      child: Row(children: [
        Expanded(
          child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
            Text(label, style: const TextStyle(fontWeight: FontWeight.w600)),
            if (sub != null) Text(sub!, style: t.textTheme.bodySmall),
          ]),
        ),
        IconButton.filledTonal(
          onPressed: value > 0 ? () => onChanged((value - step).clamp(0, 1 << 30)) : null,
          icon: const Icon(Icons.remove),
          tooltip: 'Kurangi',
        ),
        SizedBox(width: 40, child: Text('$value', textAlign: TextAlign.center, style: t.textTheme.titleMedium?.copyWith(fontWeight: FontWeight.w700))),
        IconButton.filledTonal(
          onPressed: max == null || value < max! ? () => onChanged(max == null ? value + step : (value + step).clamp(0, max!)) : null,
          icon: const Icon(Icons.add),
          tooltip: 'Tambah',
        ),
      ]),
    );
  }
}

void showError(BuildContext context, Object e) {
  ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text('$e'), backgroundColor: Theme.of(context).colorScheme.error));
}

void showInfo(BuildContext context, String msg) {
  ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text(msg)));
}
