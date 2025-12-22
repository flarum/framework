import app from '../../admin/app';
import Button from '../../common/components/Button';
import EditCustomCssModal from './EditCustomCssModal';
import EditCustomHeaderModal from './EditCustomHeaderModal';
import EditCustomFooterModal from './EditCustomFooterModal';
import UploadImageButton from '../../common/components/UploadImageButton';
import AdminPage from './AdminPage';
import ItemList from '../../common/utils/ItemList';
import type Mithril from 'mithril';
import Form from '../../common/components/Form';
import FieldSet from '../../common/components/FieldSet';
import ThemeMode from '../../common/components/ThemeMode';

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
    return this.contentItems().toArray();
  }

  contentItems(): ItemList<Mithril.Children> {
    const items = new ItemList<Mithril.Children>();

    items.add(
      'colors',
      <Form>
        <FieldSet
          className="AppearancePage-colors"
          label={app.translator.trans('core.admin.appearance.colors_heading')}
          description={app.translator.trans('core.admin.appearance.colors_text')}
        >
          {this.colorItems().toArray()}
        </FieldSet>
      </Form>,
      100
    );

    items.add('branding', <Form>{this.brandingItems().toArray()}</Form>, 90);

    return items;
  }

  brandingItems(): ItemList<Mithril.Children> {
    const items = new ItemList<Mithril.Children>();

    items.add(
      'logo',
      <div className="Form-group">
        <label>{app.translator.trans('core.admin.appearance.logo_heading')}</label>
        <div className="helpText">{app.translator.trans('core.admin.appearance.logo_text')}</div>
        <UploadImageButton name="logo" routePath="logo" value={app.data.settings.logo_path} url={app.forum.attribute('logoUrl')} />
      </div>,
      100
    );

    items.add(
      'favicon',
      <div className="Form-group">
        <label>{app.translator.trans('core.admin.appearance.favicon_heading')}</label>
        <div className="helpText">{app.translator.trans('core.admin.appearance.favicon_text')}</div>
        <UploadImageButton name="favicon" routePath="favicon" value={app.data.settings.favicon_path} url={app.forum.attribute('faviconUrl')} />
      </div>,
      90
    );

    items.add(
      'custom-header',
      <div className="Form-group">
        <label>{app.translator.trans('core.admin.appearance.custom_header_heading')}</label>
        <div className="helpText">{app.translator.trans('core.admin.appearance.custom_header_text')}</div>
        <Button className="Button" onclick={() => app.modal.show(EditCustomHeaderModal)}>
          {app.translator.trans('core.admin.appearance.edit_header_button')}
        </Button>
      </div>,
      80
    );

    items.add(
      'custom-footer',
      <div className="Form-group">
        <label>{app.translator.trans('core.admin.appearance.custom_footer_heading')}</label>
        <div className="helpText">{app.translator.trans('core.admin.appearance.custom_footer_text')}</div>
        <Button className="Button" onclick={() => app.modal.show(EditCustomFooterModal)}>
          {app.translator.trans('core.admin.appearance.edit_footer_button')}
        </Button>
      </div>,
      70
    );

    items.add(
      'custom-css',
      <div className="Form-group">
        <label>{app.translator.trans('core.admin.appearance.custom_styles_heading')}</label>
        <div className="helpText">{app.translator.trans('core.admin.appearance.custom_styles_text')}</div>
        <Button className="Button" onclick={() => app.modal.show(EditCustomCssModal)}>
          {app.translator.trans('core.admin.appearance.edit_css_button')}
        </Button>
      </div>,
      60
    );

    return items;
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
      'theme-modes',
      this.buildSettingComponent(function () {
        return (
          <div className="Form-group">
            <label>{app.translator.trans('core.admin.appearance.color_scheme_label')}</label>
            <div className="ThemeMode-list">
              {ThemeMode.colorSchemes.map((mode) => (
                <ThemeMode
                  mode={mode.id}
                  label={mode.label || app.translator.trans('core.admin.appearance.color_schemes.' + mode.id.replace('-', '_') + '_mode_label')}
                  onclick={() => {
                    this.setting('color_scheme')(mode.id);

                    this.setting('allow_user_color_scheme')(mode.id === 'auto' ? '1' : '0');

                    app.setColorScheme(mode.id);
                  }}
                  selected={this.setting('color_scheme')() === mode.id}
                />
              ))}
            </div>
          </div>
        );
      }),
      60
    );

    items.add(
      'colored-header',
      this.buildSettingComponent({
        type: 'switch',
        setting: 'theme_colored_header',
        label: app.translator.trans('core.admin.appearance.colored_header_label'),
        onchange: (value: boolean) => {
          this.setting('theme_colored_header')(value ? '1' : '0');
          app.setColoredHeader(value);
        },
      }),
      50
    );

    items.add('submit', <div className="Form-group">{this.submitButton()}</div>, 0);

    return items;
  }

  onsaved() {
    window.location.reload();
  }

  static register() {
    app.generalIndex.group('core-appearance', {
      label: app.translator.trans('core.admin.appearance.title', {}, true),
      icon: {
        name: 'fas fa-paint-brush',
      },
      link: app.route('appearance'),
    });

    app.generalIndex.for('core-appearance').add('settings', [
      {
        id: 'colors_heading',
        label: app.translator.trans('core.admin.appearance.colors_heading', {}, true),
        help: app.translator.trans('core.admin.appearance.colors_text', {}, true),
      },
      {
        id: 'color_scheme',
        label: app.translator.trans('core.admin.appearance.color_scheme_label', {}, true),
      },
      {
        id: 'colored_header',
        label: app.translator.trans('core.admin.appearance.colored_header_label', {}, true),
      },
      {
        id: 'logo_heading',
        label: app.translator.trans('core.admin.appearance.logo_heading', {}, true),
        help: app.translator.trans('core.admin.appearance.logo_text', {}, true),
      },
      {
        id: 'favicon_heading',
        label: app.translator.trans('core.admin.appearance.favicon_heading', {}, true),
        help: app.translator.trans('core.admin.appearance.favicon_text', {}, true),
      },
      {
        id: 'custom_header_heading',
        label: app.translator.trans('core.admin.appearance.custom_header_heading', {}, true),
        help: app.translator.trans('core.admin.appearance.custom_header_text', {}, true),
      },
      {
        id: 'custom_footer_heading',
        label: app.translator.trans('core.admin.appearance.custom_footer_heading', {}, true),
        help: app.translator.trans('core.admin.appearance.custom_footer_text', {}, true),
      },
      {
        id: 'custom_styles_heading',
        label: app.translator.trans('core.admin.appearance.custom_styles_heading', {}, true),
        help: app.translator.trans('core.admin.appearance.custom_styles_text', {}, true),
      },
    ]);
  }
}
