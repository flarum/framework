import MarkdownArea from 'mdarea';
import BasicEditorDriver from 'flarum/utils/BasicEditorDriver';

export default class MarkdownAreaEditorDriver extends BasicEditorDriver {
  build(dom, params) {
    super.build(dom, params);
    this.el.id = params.textareaId;

    // We can't bind shortcutHandler directly in case `build`
    // runs before MarkdownToolbar's `oninit`.
    this.el.addEventListener('keydown', function (e) {
      return params.shortcutHandler(...arguments);
    });

    this.mdarea = new MarkdownArea(this.el, {
      keyMap: {
        indent: ['Ctrl+m'],
        outdent: ['Ctrl+M'],
        inline: []
      }
    });
  }

  destroy() {
    this.mdarea.destroy();
    super.destroy();
  }
}
