/* eslint-disable */
// eslint-disable-next-line import/no-extraneous-dependencies
// noinspection JSFileReferences,NpmUsedModulesInstalled
import tippy from 'tippy.js';
import StringHelpers from '@/helpers/string_helpers';

export default class MainDirectives {
  static init(Vue) {
    Vue.directive('focus', {
      inserted(el) {
        el.addEventListener('mouseenter', (e) => {
          e.target.focus();
        });
      },
    });

    Vue.directive('trim', {
      inserted(el) {
        el.innerHTML = el.innerHTML.trim();
      },
    });

    function tooltipOptions(tooltipContent, binding) {
      let placement = 'top';

      if (binding.modifiers.left) {
        placement = 'left';
      }

      if (binding.modifiers.right) {
        placement = 'right';
      }

      if (binding.modifiers.bottom) {
        placement = 'bottom';
      }

      const distance = Object.keys(binding.modifiers).find((b) => Number.isInteger(Number(b))) || 15;
      let delay = Object.keys(binding.modifiers)
        .find((b) => /d\d+/.test(b));

      delay = delay ? delay.substr(1) : null;

      return {
        arrow: true,
        content: StringHelpers.stylize(tooltipContent),
        placement,
        distance: Number(distance),
        delay: [delay, null],
      };
    }

    Vue.directive('tooltip', {
      inserted(el, binding, vNode) {
        const { elm } = vNode;
        const options = tooltipOptions(elm.getAttribute('content'), binding);
        tippy(el, options);
      },

      componentUpdated(el, binding, vNode) {
        if (vNode.elm && vNode.elm._tippy) {
          const { elm } = vNode;
          const options = tooltipOptions(elm.getAttribute('content'), binding);
          elm._tippy.set(options);
        }
      },
    });
  }
}
