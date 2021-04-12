import lodash from 'lodash';
import RegexHelpers from '@/helpers/regex_helpers';

import pluralize from 'pluralize';

import { snakeCase } from 'snake-case';

export default class StringHelpers {
  static ellipse(string, maxLength = 50, showEllipses = true) {
    if (!string) {
      return string;
    }

    return string.length <= maxLength ? string : `${string.substr(0, maxLength)}${showEllipses ? '...' : ''}`;
  }

  static pluralize(string) {
    return pluralize(string);
  }

  static singular(string) {
    return pluralize.singular(string);
  }

  static snakeCase(string) {
    return snakeCase(string);
  }

  static substrCount(string, substr) {
    return lodash.countBy(string)[substr] || 0;
  }

  static humanize(string) {
    return string.replace(/[-_]/g, ' ').split(' ').map((s) => s.substr(0, 1).toUpperCase() + s.substr(1)).join(' ');
  }

  static studly(string) {
    return string.replace(/[-_]/g, ' ').split(' ').map((s) => s.substr(0, 1).toUpperCase() + s.substr(1)).join('');
  }

  static lcFirst(string) {
    if (!string || string.trim().length === 0) {
      return string;
    }

    return string.substr(0, 1).toLowerCase() + string.substr(1);
  }

  // TODO: Allow variable pattern size
  static stylize(string) {
    const map = [
      {
        pattern: '[c]',
        class: 'text-complete',
      },
      {
        pattern: '[d]',
        class: 'text-danger',
      },
      {
        pattern: '[s]',
        class: 'text-success',
      },
      {
        pattern: '[p]',
        class: 'text-primary',
      },
      {
        pattern: '[w]',
        class: 'text-warning',
      },
      {
        pattern: '[g]',
        class: 'text-green',
      },
      {
        pattern: '[✓]',
        class: '',
        icon: 'fa-check',
      },
      {
        pattern: '[!]',
        class: '',
        icon: 'fa-exclamation-triangle',
      },
    ];

    let stylized = string;

    const closingTags = [];

    const decorators = RegexHelpers.getMatches('\\[[a-z#!]{1,2}\\]', string);

    decorators.forEach((decorator) => {
      const isBold = decorator.length === 4;
      const mapped = map.find((k) => k.pattern === (isBold ? `[${decorator.substr(2)}` : decorator));
      if (mapped) {
        const classes = isBold ? `bold ${mapped.class}` : mapped.class;

        const pattern = isBold ? `\\[b${mapped.pattern.substr(1, mapped.pattern.length - 1)}` : mapped.pattern.replace('[', '\\[');

        let replacement = `<span class="${classes}">`;

        if (mapped.icon) {
          replacement += `<i class="fa ${mapped.icon}"></i>`;
        }

        closingTags.push(`\\[/${isBold ? 'b' : ''}${mapped.pattern.substr(1)}`);

        stylized = stylized.replace(new RegExp(pattern, 's'), replacement);
      }
    });

    stylized = closingTags.reduce((acc, tag) => acc.replace(new RegExp(tag, 's'), '</span>'), stylized);

    return stylized;
  }
}
