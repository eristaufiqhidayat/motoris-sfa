import 'package:flutter/material.dart';

/// Warna korporat Oasis (diambil dari logo).
class OasisColors {
  static const navy = Color(0xFF293694);
  static const navyDark = Color(0xFF1F2A78);
  static const teal = Color(0xFF18A6B1);
}

/// Logo Oasis di atas latar putih, aman dipakai di atas warna navy maupun mode gelap.
class OasisLogo extends StatelessWidget {
  const OasisLogo({super.key, this.height = 28, this.padding = const EdgeInsets.symmetric(horizontal: 8, vertical: 4), this.withTagline = true});
  final double height;
  final EdgeInsets padding;

  /// false = tanpa tulisan "Air minum dalam kemasan", untuk ukuran kecil seperti app bar.
  final bool withTagline;

  @override
  Widget build(BuildContext context) => Container(
        padding: padding,
        decoration: BoxDecoration(color: Colors.white, borderRadius: BorderRadius.circular(8)),
        child: Image.asset(withTagline ? 'assets/oasis-logo.png' : 'assets/oasis-mark.png', height: height, semanticLabel: 'Oasis'),
      );
}
