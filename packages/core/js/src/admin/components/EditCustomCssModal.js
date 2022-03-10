import app from '../../admin/app';
import SettingsModal from './SettingsModal';

export default class EditCustomCssModal extends SettingsModal {
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
