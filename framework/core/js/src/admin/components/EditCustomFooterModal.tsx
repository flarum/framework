import app from '../../admin/app';
import SettingsModal from './SettingsModal';

export default class EditCustomFooterModal extends SettingsModal {
  className() {
    return 'EditCustomFooterModal TextareaCodeModal Modal--large';
  }

  title() {
    return app.translator.trans('core.admin.edit_footer.title');
  }

  form() {
    return [
      <p>{app.translator.trans('core.admin.edit_footer.customize_text')}</p>,
      <div className="Form-group">
        <textarea className="FormControl" rows="30" bidi={this.setting('custom_footer')} />
      </div>,
    ];
  }

  onsaved() {
    window.location.reload();
  }
}
