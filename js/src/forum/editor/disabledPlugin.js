import { Plugin, PluginKey } from 'prosemirror-state';

export default function disabledPlugin(text) {
  return new Plugin({
    key: new PluginKey('disabled'),

    props: {
      editable: (state) => {
        return !state.disabled$;
      },
    },

    state: {
      init(config) {
        return config.disabled;
      },

      apply(tr, curr) {
        const disabled = tr.getMeta('disabled');

        if (disabled !== undefined) {
          return disabled;
        }

        return curr;
      },
    },
  });
}
