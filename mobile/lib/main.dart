import 'package:flutter/material.dart';
import 'package:intl/date_symbol_data_local.dart';
import 'package:provider/provider.dart';

import 'api/api_client.dart';
import 'brand.dart';
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
    ThemeData theme(Brightness b) {
      final light = b == Brightness.light;
      final scheme = ColorScheme.fromSeed(seedColor: OasisColors.navy, brightness: b).copyWith(
        primary: light ? OasisColors.navy : const Color(0xFFB9C1F5),
        secondary: OasisColors.teal,
        tertiary: OasisColors.teal,
        surface: light ? Colors.white : null,
      );
      return ThemeData(
          colorScheme: scheme,
          useMaterial3: true,
          scaffoldBackgroundColor: light ? const Color(0xFFF5F6FB) : null,
          appBarTheme: AppBarTheme(
            backgroundColor: light ? OasisColors.navy : scheme.surfaceContainer,
            foregroundColor: light ? Colors.white : scheme.onSurface,
          ),
          progressIndicatorTheme: const ProgressIndicatorThemeData(color: OasisColors.teal),
          cardTheme: CardThemeData(margin: EdgeInsets.zero, color: light ? Colors.white : null),
          inputDecorationTheme: const InputDecorationTheme(border: OutlineInputBorder()),
        );
    }

    return MaterialApp(
      title: 'Oasis Sales',
      debugShowCheckedModeBanner: false,
      theme: theme(Brightness.light),
      darkTheme: theme(Brightness.dark),
      home: Consumer<AppState>(builder: (context, s, _) {
        if (s.booting) return const Scaffold(backgroundColor: Colors.white, body: Center(child: OasisLogo(height: 90)));
        return s.loggedIn ? const HomeShell() : const LoginScreen();
      }),
    );
  }
}
