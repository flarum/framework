import MarkdownArea from 'mdarea';
import BasicEditorDriver from 'flarum/utils/BasicEditorDriver';

export class MarkdownEditorFlarumExtension {
  constructor(oninput, callInputListeners, onsubmit) {
    this.oninput = oninput;
    this.callInputListeners = callInputListeners;
    this.onsubmit = onsubmit;
  }

  handleKey(
    prefix,
    selection,
    postfix,
    evt
  ) {
    // setTimeout executes after the call stack has cleared,
    // so any DOM changes originating from mdarea (e.g. executing an undo)
    // will be finished by then. At that time, `e.target.value` will represent
    // the updated value of the textarea in response to the keypress.
    // Unfortunately, this doesn't work without a value for mobile safari,
    // so we need to set 25ms as an arbitrary timeout.
    setTimeout(() => {
      this.oninput(evt.target.value);

      if ((evt.metaKey || evt.ctrlKey) && evt.key === 'Enter') {
        return this.onsubmit();
      }

      this.callInputListeners(evt);
    }, 25);
  }
}

export default class MarkdownEditorDriver extends BasicEditorDriver {
  build(dom, params) {
    if (app.forum.attribute('flarum-markdown.mdarea')) {
      this.el.className = params.classNames.join(' ');
      this.el.disabled = params.disabled;
      this.el.placeholder = params.placeholder;
      this.el.value = params.value;

      dom.append(this.el);

      const callInputListeners = (e) => {
        params.inputListeners.forEach((listener) => {
          listener();
        });

        e.redraw = false;
      };

      // Our mdarea extension won't detect programmatic changes via
      // the `app.composer.editor api.
      this.el.addEventListener('input', function (e) {
        if (e instanceof CustomEvent) {
          params.oninput(e.target.value);
          callInputListeners(e);
        }
      });

      // This one can't be run through mdarea, but that doesn't matter
      // because mdarea doesn't change value in response to clicks.
      this.el.addEventListener('click', callInputListeners);

      this.mdarea = new MarkdownArea(this.el, {
        keyMap: {
          indent: ['Ctrl+m'],
          outdent: ['Ctrl+M'],
          inline: []
        },
        extensions: [
          new MarkdownEditorFlarumExtension(params.oninput, callInputListeners, params.onsubmit)
        ]
      });
    } else {
      super.build(dom, params);
    }
    this.el.id = params.textareaId;

    // We can't bind shortcutHandler directly in case `build`
    // runs before MarkdownToolbar's `oninit`.
    this.el.addEventListener('keydown', function (e) {
      return params.shortcutHandler(...arguments);
    });
  }

  destroy() {
    if (app.forum.attribute('flarum-markdown.mdarea')) {
      this.mdarea.destroy();
    }
    super.destroy();
  }
}
