import app from '../../admin/app';
import Button from '../../common/components/Button';
import EditCustomCssModal from './EditCustomCssModal';
import EditCustomHeaderModal from './EditCustomHeaderModal';
import EditCustomFooterModal from './EditCustomFooterModal';
import UploadImageButton from './UploadImageButton';
import AdminPage from './AdminPage';
import ItemList from '../../common/utils/ItemList';
import type Mithril from 'mithril';
import Form from '../../common/components/Form';
import FieldSet from '../../common/components/FieldSet';

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
    return (
      <>
        <Form>
          <FieldSet
            className="AppearancePage-colors"
            label={app.translator.trans('core.admin.appearance.colors_heading')}
            description={app.translator.trans('core.admin.appearance.colors_text')}
          >
            {this.colorItems().toArray()}
          </FieldSet>
        </Form>

        <Form>
          <div className="Form-group">
            <label>{app.translator.trans('core.admin.appearance.logo_heading')}</label>
            <div className="helpText">{app.translator.trans('core.admin.appearance.logo_text')}</div>
            <UploadImageButton name="logo" routePath="logo" value={app.data.settings.logo_path} url={app.forum.attribute('logoUrl')} />
          </div>

          <div className="Form-group">
            <label>{app.translator.trans('core.admin.appearance.favicon_heading')}</label>
            <div className="helpText">{app.translator.trans('core.admin.appearance.favicon_text')}</div>
            <UploadImageButton name="favicon" routePath="favicon" value={app.data.settings.favicon_path} url={app.forum.attribute('faviconUrl')} />
          </div>

          <div className="Form-group">
            <label>{app.translator.trans('core.admin.appearance.custom_header_heading')}</label>
            <div className="helpText">{app.translator.trans('core.admin.appearance.custom_header_text')}</div>
            <Button className="Button" onclick={() => app.modal.show(EditCustomHeaderModal)}>
              {app.translator.trans('core.admin.appearance.edit_header_button')}
            </Button>
          </div>

          <div className="Form-group">
            <label>{app.translator.trans('core.admin.appearance.custom_footer_heading')}</label>
            <div className="helpText">{app.translator.trans('core.admin.appearance.custom_footer_text')}</div>
            <Button className="Button" onclick={() => app.modal.show(EditCustomFooterModal)}>
              {app.translator.trans('core.admin.appearance.edit_footer_button')}
            </Button>
          </div>

          <div className="Form-group">
            <label>{app.translator.trans('core.admin.appearance.custom_styles_heading')}</label>
            <div className="helpText">{app.translator.trans('core.admin.appearance.custom_styles_text')}</div>
            <Button className="Button" onclick={() => app.modal.show(EditCustomCssModal)}>
              {app.translator.trans('core.admin.appearance.edit_css_button')}
            </Button>
          </div>
        </Form>
      </>
    );
  }

  colorItems() {
    const items = new ItemList<Mithril.Children>();

    items.add(
      'theme-colors',
      <div className="AppearancePage-colors-input">
        {this.buildSettingComponent({
          type: 'color-preview',
          setting: 'theme_primary_color',
          placeholder: '#aaaaaa',
          ariaLabel: app.translator.trans('core.admin.appearance.colors_primary_label'),
        })}
        {this.buildSettingComponent({
          type: 'color-preview',
          setting: 'theme_secondary_color',
          placeholder: '#aaaaaa',
          ariaLabel: app.translator.trans('core.admin.appearance.colors_secondary_label'),
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

    items.add('submit', <div className="Form-group">{this.submitButton()}</div>, 0);

    return items;
  }

  onsaved() {
    window.location.reload();
  }
}
