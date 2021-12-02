import app from '../../admin/app';
import Button from '../../common/components/Button';
import EditCustomCssModal from './EditCustomCssModal';
import EditCustomHeaderModal from './EditCustomHeaderModal';
import EditCustomFooterModal from './EditCustomFooterModal';
import UploadImageButton from './UploadImageButton';
import AdminPage from './AdminPage';
import ItemList from '../../common/utils/ItemList';

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
          {this.colorItems().toArray()}
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

  colorItems() {
    const items = new ItemList();

    items.add('helpText', <div className="helpText">{app.translator.trans('core.admin.appearance.colors_text')}</div>, 80);

    items.add(
      'theme-colors',
      <div className="AppearancePage-colors-input">
        {this.buildSettingComponent({
          type: 'color-preview',
          setting: 'theme_primary_color',
          placeholder: '#aaaaaa',
        })}
        {this.buildSettingComponent({
          type: 'color-preview',
          setting: 'theme_secondary_color',
          placeholder: '#aaaaaa',
        })}
      </div>,
      70
    );

    items.add(
      'dark-mode',
      this.buildSettingComponent({
        type: 'switch',
        setting: 'theme_dark_mode',
        label: app.translator.trans('core.admin.appearance.dark_mode_label'),
      }),
      60
    );

    items.add(
      'colored-header',
      this.buildSettingComponent({
        type: 'switch',
        setting: 'theme_colored_header',
        label: app.translator.trans('core.admin.appearance.colored_header_label'),
      }),
      50
    );

    items.add('submit', this.submitButton(), 0);

    return items;
  }

  onsaved() {
    window.location.reload();
  }
}
