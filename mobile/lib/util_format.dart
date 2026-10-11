import 'package:intl/intl.dart';

final _rupiah = NumberFormat.currency(locale: 'id_ID', symbol: 'Rp', decimalDigits: 0);
final _angka = NumberFormat.decimalPattern('id_ID');

String rupiah(num n) => _rupiah.format(n);
String angka(num n) => _angka.format(n);
