import p from 'path';

export default class FilesystemHelpers {
  static sep() {
    return p.sep;
  }

  static dir(path) {
    if (!path) {
      return path;
    }

    return p.dirname(path);
  }

  static fn(path) {
    if (!path) {
      return path;
    }

    return p.basename(path);
  }

  static fnNoExt(path) {
    if (!path) {
      return path;
    }

    const fn = FilesystemHelpers.fn(path);

    return fn.substr(0, fn.lastIndexOf('.'));
  }

  static ext(path) {
    if (!path) {
      return path;
    }

    return p.extname(path);
  }

  static isRelativePath(path) {
    return !path ? false : (path.startsWith('.')) && (!path.startsWith('http') && !path.startsWith('//'));
  }
}
