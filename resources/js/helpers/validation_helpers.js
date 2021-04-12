export default class ValidationHelpers {
  static ensureAlphaNumericOnly(input) {
    const pattern = /^[a-zA-Z0-9]+$/;
    const passed = new RegExp(pattern, 'ig').test(input);

    return { pattern, passed };
  }

  static isValidTableName(input) {
    const pattern = /^[a-zA-Z0-9-_]+$/;
    const passed = new RegExp(pattern, 'ig').test(input);

    return { pattern, passed };
  }

  static isValidTableColumnName(input, forceLowercase) {
    const pattern = forceLowercase ? /^[a-z0-9_]+$/ : /^[a-zA-Z0-9_]+$/;
    const passed = new RegExp(pattern, 'ig').test(input);

    return { pattern, passed };
  }

  static isValidProjectName(input) {
    const pattern = /^[a-z0-9_]{2,32}$/;
    const passed = new RegExp(pattern).test(input);

    return { pattern, passed };
  }
}
