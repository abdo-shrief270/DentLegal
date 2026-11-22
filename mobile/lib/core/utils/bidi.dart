/// Wraps a left-to-right token (case code, licence number, phone) in a Unicode isolate (LRI … PDI)
/// so it keeps its order inside Arabic text, e.g. «21-RD-0045871» instead of «RD-0045871-21».
String ltr(String text) => '\u2066$text\u2069';
