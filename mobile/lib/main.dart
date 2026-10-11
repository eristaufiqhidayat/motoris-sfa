import 'package:flutter/material.dart';
import 'package:intl/date_symbol_data_local.dart';
import 'package:provider/provider.dart';

import 'api/api_client.dart';
import 'screens/home_shell.dart';
import 'screens/login_screen.dart';
import 'state/app_state.dart';

Future<void> main() async {
  WidgetsFlutterBinding.ensureInitialized();
  await initializeDateFormatting('id_ID');
  runApp(ChangeNotifierProvider(create: (_) => AppState(ApiClient())..boot(), child: const OasisSalesApp()));
}

class OasisSalesApp extends StatelessWidget {
  const OasisSalesApp({super.key});

  @override
  Widget build(BuildContext context) {
    const seed = Color(0xFF1662C4);
    ThemeData theme(Brightness b) => ThemeData(
          colorScheme: ColorScheme.fromSeed(seedColor: seed, brightness: b),
          useMaterial3: true,
          cardTheme: const CardThemeData(margin: EdgeInsets.zero),
          inputDecorationTheme: const InputDecorationTheme(border: OutlineInputBorder()),
        );

    return MaterialApp(
      title: 'Oasis Sales',
      debugShowCheckedModeBanner: false,
      theme: theme(Brightness.light),
      darkTheme: theme(Brightness.dark),
      home: Consumer<AppState>(builder: (context, s, _) {
        if (s.booting) return const Scaffold(body: Center(child: CircularProgressIndicator()));
        return s.loggedIn ? const HomeShell() : const LoginScreen();
      }),
    );
  }
}
