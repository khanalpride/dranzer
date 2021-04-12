export default class RegexHelpers {
  static getFirstMatch(pattern, string, flags = 'is') {
    if (!string) {
      return null;
    }

    const result = new RegExp(pattern, flags).exec(string);
    return result && result.length ? result[0] : null;
  }

  static getMatches(pattern, string, flags = 'ig') {
    if (!string) {
      return [];
    }

    const regex = new RegExp(pattern, flags);

    let m;

    const matches = [];

    // eslint-disable-next-line no-cond-assign
    while ((m = regex.exec(string)) !== null) {
      // This is necessary to avoid infinite loops with zero-width matches
      if (m.index === regex.lastIndex) {
        regex.lastIndex += 1;
      }

      // The result can be accessed through the `m`-variable.
      m.forEach((match) => {
        matches.push(match);
      });
    }

    return matches;
  }

  static isAlphaNumericOnly(input) {
    return /^[a-zA-Z0-9]+$/.test(input);
  }

  static isNumericAlphaNumericOnly(input) {
    return /^[a-zA-Z][a-zA-Z0-9]+$/.test(input);
  }
}
