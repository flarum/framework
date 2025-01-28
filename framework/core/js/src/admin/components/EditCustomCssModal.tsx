import app from '../../admin/app';
import SettingsModal, { type ISettingsModalAttrs } from './SettingsModal';
import Mithril from 'mithril';

export default class EditCustomCssModal extends SettingsModal {
  oninit(vnode: Mithril.Vnode<ISettingsModalAttrs, this>) {
    super.oninit(vnode);

    if (this.setting('custom_less_error')()) {
      this.alertAttrs = {
        type: 'error',
        content: this.setting('custom_less_error')(),
      };
    }
  }

  className() {
    return 'EditCustomCssModal TextareaCodeModal Modal--large';
  }

  title() {
    return app.translator.trans('core.admin.edit_css.title');
  }

  form() {
    return [
      <p>
        {app.translator.trans('core.admin.edit_css.customize_text', {
          a: <a href="https://github.com/flarum/core/tree/master/less" target="_blank" />,
        })}
      </p>,
      <div className="Form-group">
        <textarea className="FormControl" rows="30" bidi={this.setting('custom_less')} spellcheck={false} />
      </div>,
    ];
  }

  onsaved() {
    window.location.reload();
  }
}
