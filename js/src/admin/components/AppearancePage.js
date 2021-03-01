import Button from '../../common/components/Button';
import EditCustomCssModal from './EditCustomCssModal';
import EditCustomHeaderModal from './EditCustomHeaderModal';
import EditCustomFooterModal from './EditCustomFooterModal';
import UploadImageButton from './UploadImageButton';
import AdminPage from './AdminPage';

export default class AppearancePage extends AdminPage {
  headerInfo() {
    return {
      className: 'AppearancePage',
      icon: 'fas fa-paint-brush',
      title: app.translator.trans('core.admin.appearance.title'),
      description: app.translator.trans('core.admin.appearance.description'),
    };
  }

  content() {
    return [
      <div className="Form">
        <fieldset className="AppearancePage-colors">
          <legend>{app.translator.trans('core.admin.appearance.colors_heading')}</legend>
          <div className="helpText">{app.translator.trans('core.admin.appearance.colors_text')}</div>

          <div className="AppearancePage-colors-input">
            {this.buildSettingComponent({
              type: 'text',
              setting: 'theme_primary_color',
              placeholder: '#aaaaaa',
            })}
            {this.buildSettingComponent({
              type: 'text',
              setting: 'theme_secondary_color',
              placeholder: '#aaaaaa',
            })}
          </div>

          {this.buildSettingComponent({
            type: 'switch',
            setting: 'theme_dark_mode',
            label: app.translator.trans('core.admin.appearance.dark_mode_label'),
          })}

          {this.buildSettingComponent({
            type: 'switch',
            setting: 'theme_colored_header',
            label: app.translator.trans('core.admin.appearance.colored_header_label'),
          })}

          {this.submitButton()}
        </fieldset>
      </div>,

      <fieldset>
        <legend>{app.translator.trans('core.admin.appearance.logo_heading')}</legend>
        <div className="helpText">{app.translator.trans('core.admin.appearance.logo_text')}</div>
        <UploadImageButton name="logo" />
      </fieldset>,

      <fieldset>
        <legend>{app.translator.trans('core.admin.appearance.favicon_heading')}</legend>
        <div className="helpText">{app.translator.trans('core.admin.appearance.favicon_text')}</div>
        <UploadImageButton name="favicon" />
      </fieldset>,

      <fieldset>
        <legend>{app.translator.trans('core.admin.appearance.custom_header_heading')}</legend>
        <div className="helpText">{app.translator.trans('core.admin.appearance.custom_header_text')}</div>
        {Button.component(
          {
            className: 'Button',
            onclick: () => app.modal.show(EditCustomHeaderModal),
          },
          app.translator.trans('core.admin.appearance.edit_header_button')
        )}
      </fieldset>,

      <fieldset>
        <legend>{app.translator.trans('core.admin.appearance.custom_footer_heading')}</legend>
        <div className="helpText">{app.translator.trans('core.admin.appearance.custom_footer_text')}</div>
        {Button.component(
          {
            className: 'Button',
            onclick: () => app.modal.show(EditCustomFooterModal),
          },
          app.translator.trans('core.admin.appearance.edit_footer_button')
        )}
      </fieldset>,

      <fieldset>
        <legend>{app.translator.trans('core.admin.appearance.custom_styles_heading')}</legend>
        <div className="helpText">{app.translator.trans('core.admin.appearance.custom_styles_text')}</div>
        {Button.component(
          {
            className: 'Button',
            onclick: () => app.modal.show(EditCustomCssModal),
          },
          app.translator.trans('core.admin.appearance.edit_css_button')
        )}
      </fieldset>,
    ];
  }

  onsaved() {
    window.location.reload();
  }

  saveSettings(e) {
    e.preventDefault();

    const hex = /^#[0-9a-f]{3}([0-9a-f]{3})?$/i;

    if (!hex.test(this.settings['theme_primary_color']()) || !hex.test(this.settings['theme_secondary_color']())) {
      alert(app.translator.trans('core.admin.appearance.enter_hex_message'));
      return;
    }

    super.saveSettings(e);
  }
}
