export default class BladeHelpers {
  static bladeTemplateFilename(partialName, appendExtension = true) {
    if (!partialName) {
      return 'unnamed_layout.blade.php';
    }

    if (appendExtension) {
      return partialName.substr(partialName.length - 10) !== '.blade.php' ? `${partialName}.blade.php` : partialName;
    }

    return partialName;
  }
}
