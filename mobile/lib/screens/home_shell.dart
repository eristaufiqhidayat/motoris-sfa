import 'package:flutter/material.dart';
import 'package:provider/provider.dart';

import '../brand.dart';
import '../state/app_state.dart';
import 'beranda_screen.dart';
import 'rute_screen.dart';
import 'setoran_screen.dart';

class HomeShell extends StatefulWidget {
  const HomeShell({super.key});

  @override
  State<HomeShell> createState() => _HomeShellState();
}

class _HomeShellState extends State<HomeShell> {
  int _tab = 0;

  @override
  Widget build(BuildContext context) {
    final s = context.watch<AppState>();
    final pages = [
      BerandaScreen(onBukaRute: () => setState(() => _tab = 1)),
      const RuteScreen(),
      const SetoranScreen(),
    ];
    return Scaffold(
      appBar: AppBar(
        leading: const Padding(padding: EdgeInsets.only(left: 12), child: Center(child: OasisLogo(height: 26, withTagline: false, padding: EdgeInsets.symmetric(horizontal: 6, vertical: 3)))),
        leadingWidth: 100,
        title: Text(['Beranda', 'Rute ${s.today?.ruteKode ?? ''}', 'Setoran'][_tab]),
        actions: [
          IconButton(onPressed: s.loading ? null : s.refresh, icon: const Icon(Icons.refresh), tooltip: 'Muat ulang'),
          PopupMenuButton<String>(
            onSelected: (_) => s.logout(),
            itemBuilder: (_) => [const PopupMenuItem(value: 'out', child: Text('Keluar'))],
          ),
        ],
      ),
      body: s.error != null && s.today == null
          ? Center(child: Padding(padding: const EdgeInsets.all(24), child: Text(s.error!, textAlign: TextAlign.center)))
          : pages[_tab],
      bottomNavigationBar: NavigationBar(
        selectedIndex: _tab,
        onDestinationSelected: (i) => setState(() => _tab = i),
        destinations: const [
          NavigationDestination(icon: Icon(Icons.home_outlined), selectedIcon: Icon(Icons.home), label: 'Beranda'),
          NavigationDestination(icon: Icon(Icons.route_outlined), selectedIcon: Icon(Icons.route), label: 'Rute'),
          NavigationDestination(icon: Icon(Icons.payments_outlined), selectedIcon: Icon(Icons.payments), label: 'Setoran'),
        ],
      ),
    );
  }
}
