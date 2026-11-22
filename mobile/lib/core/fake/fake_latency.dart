/// Simulated network delay for the fake repositories (synthetic data only, no backend yet).
Future<void> fakeLatency([int milliseconds = 450]) => Future<void>.delayed(Duration(milliseconds: milliseconds));
