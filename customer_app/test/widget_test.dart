import 'package:flutter_test/flutter_test.dart';
import 'package:madd_customer/core/config.dart';

void main() {
  test('API base defaults to production portal', () {
    expect(AppConfig.apiBase, contains('my.madd.ps'));
  });
}
