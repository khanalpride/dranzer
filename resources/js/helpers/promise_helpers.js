export default class PromiseHelpers {
  static async sleep(ms) {
    return new Promise((resolve) => setTimeout(resolve, ms));
  }
}
