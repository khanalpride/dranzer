export default class MainPolyFills {
  static init() {
    if (!Number.MAX_SAFE_INTEGER) {
      Number.MAX_SAFE_INTEGER = 9007199254740991; // Math.pow(2, 53) - 1;
    }
  }
}
