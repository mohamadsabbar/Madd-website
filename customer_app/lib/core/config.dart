class AppConfig {
  /// Override at build time:
  /// `--dart-define=API_BASE=https://my.madd.ps/api`
  static const apiBase = String.fromEnvironment(
    'API_BASE',
    defaultValue: 'https://my.madd.ps/api',
  );
}
