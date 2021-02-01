import Page from '../../common/components/Page';
import Button from '../../common/components/Button';
import Switch from '../../common/components/Switch';
import Stream from '../../common/utils/Stream';
import EditCustomCssModal from './EditCustomCssModal';
import EditCustomHeaderModal from './EditCustomHeaderModal';
import EditCustomFooterModal from './EditCustomFooterModal';
import UploadImageButton from './UploadImageButton';
import saveSettings from '../utils/saveSettings';
import AdminHeader from './AdminHeader';

export default class AppearancePage extends Page {
  oninit(vnode) {
    super.oninit(vnode);

    this.primaryColor = Stream(app.data.settings.theme_primary_color);
    this.secondaryColor = Stream(app.data.settings.theme_secondary_color);
    this.darkMode = Stream(app.data.settings.theme_dark_mode);
    this.coloredHeader = Stream(app.data.settings.theme_colored_header);
  }

  view() {
    return (
      <div className="AppearancePage">
        <AdminHeader
          icon="fas fa-paint-brush"
          description={app.translator.trans('core.admin.appearance.description')}
          className="AppearancePage-header"
        >
          {app.translator.trans('core.admin.appearance.title')}
        </AdminHeader>
        <div className="container">
          <form onsubmit={this.onsubmit.bind(this)}>
            <fieldset className="AppearancePage-colors">
              <legend>{app.translator.trans('core.admin.appearance.colors_heading')}</legend>
              <div className="helpText">{app.translator.trans('core.admin.appearance.colors_text')}</div>

              <div className="AppearancePage-colors-input">
                <input className="FormControl" type="text" placeholder="#aaaaaa" bidi={this.primaryColor} />
                <input className="FormControl" type="text" placeholder="#aaaaaa" bidi={this.secondaryColor} />
              </div>

              {Switch.component(
                {
                  state: this.darkMode(),
                  onchange: this.darkMode,
                },
                app.translator.trans('core.admin.appearance.dark_mode_label')
              )}

              {Switch.component(
                {
                  state: this.coloredHeader(),
                  onchange: this.coloredHeader,
                },
                app.translator.trans('core.admin.appearance.colored_header_label')
              )}

              {Button.component(
                {
                  className: 'Button Button--primary',
                  type: 'submit',
                  loading: this.loading,
                },
                app.translator.trans('core.admin.appearance.submit_button')
              )}
            </fieldset>
          </form>

          <fieldset>
            <legend>{app.translator.trans('core.admin.appearance.logo_heading')}</legend>
            <div className="helpText">{app.translator.trans('core.admin.appearance.logo_text')}</div>
            <UploadImageButton name="logo" />
          </fieldset>

          <fieldset>
            <legend>{app.translator.trans('core.admin.appearance.favicon_heading')}</legend>
            <div className="helpText">{app.translator.trans('core.admin.appearance.favicon_text')}</div>
            <UploadImageButton name="favicon" />
          </fieldset>

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
          </fieldset>

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
          </fieldset>

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
          </fieldset>
        </div>
      </div>
    );
  }

  onsubmit(e) {
    e.preventDefault();

    const hex = /^#[0-9a-f]{3}([0-9a-f]{3})?$/i;

    if (!hex.test(this.primaryColor()) || !hex.test(this.secondaryColor())) {
      alert(app.translator.trans('core.admin.appearance.enter_hex_message'));
      return;
    }

    this.loading = true;

    saveSettings({
      theme_primary_color: this.primaryColor(),
      theme_secondary_color: this.secondaryColor(),
      theme_dark_mode: this.darkMode(),
      theme_colored_header: this.coloredHeader(),
    }).then(() => window.location.reload());
  }
}
