import app from '../../admin/app';
import AdminPage from './AdminPage';
import type { IPageAttrs } from '../../common/components/Page';
import type Mithril from 'mithril';
import Form from '../../common/components/Form';
import extractText from '../../common/utils/extractText';
import FormSectionGroup, { FormSection } from './FormSectionGroup';
import ItemList from '../../common/utils/ItemList';
import InfoTile from '../../common/components/InfoTile';
import { MaintenanceMode } from '../../common/Application';

export default class AdvancedPage<CustomAttrs extends IPageAttrs = IPageAttrs> extends AdminPage<CustomAttrs> {
  searchDriverOptions: Record<string, Record<string, string>> = {};

  oninit(vnode: Mithril.Vnode<CustomAttrs, this>) {
    super.oninit(vnode);

    const locale = this.driverLocale();

    Object.keys(app.data.searchDrivers).forEach((model) => {
      this.searchDriverOptions[model] = {};

      app.data.searchDrivers[model].forEach((option) => {
        this.searchDriverOptions[model][option] = locale.search[option] || option;
      });
    });
  }

  headerInfo() {
    return {
      className: 'AdvancedPage',
      icon: 'fas fa-cog',
      title: app.translator.trans('core.admin.advanced.title'),
      description: app.translator.trans('core.admin.advanced.description'),
    };
  }

  content() {
    return [
      <Form className="AdvancedPage-container">
        <FormSectionGroup>{this.sectionItems().toArray()}</FormSectionGroup>
        <div className="Form-group Form-controls">{this.submitButton()}</div>
      </Form>,
    ];
  }

  driverLocale(): Record<string, Record<string, string>> {
    return {
      search: {
        default: extractText(app.translator.trans('core.admin.advanced.search.driver_options.default')),
      },
    };
  }

  sectionItems() {
    const items = new ItemList<Mithril.Children>();

    items.add('search', this.searchDrivers(), 100);

    items.add('maintenance', this.maintenance(), 90);

    return items;
  }

  searchDrivers() {
    const hasOtherDrivers = Object.keys(this.searchDriverOptions).some((model) => Object.keys(this.searchDriverOptions[model]).length > 1);

    return (
      <FormSection label={app.translator.trans('core.admin.advanced.search.section_label')}>
        {hasOtherDrivers ? (
          <Form>
            {Object.keys(this.searchDriverOptions).map((model) => {
              const options = this.searchDriverOptions[model];
              const modelLocale = this.modelLocale()[model] || model;

              if (Object.keys(options).length > 1) {
                return this.buildSettingComponent({
                  type: 'select',
                  setting: `search_driver_${model}`,
                  options,
                  label: app.translator.trans('core.admin.advanced.search.driver_heading', { model: modelLocale }),
                  help: app.translator.trans('core.admin.advanced.search.driver_text', { model: modelLocale }),
                });
              }

              return null;
            })}
          </Form>
        ) : (
          <InfoTile icon="fas fa-database" className="InfoTile--warning">
            {app.translator.trans('core.admin.advanced.search.no_other_drivers')}
          </InfoTile>
        )}
      </FormSection>
    );
  }

  maintenance() {
    const safeModeExtensionsByConfig =
      JSON.stringify(app.data.safeModeExtensions) !== JSON.stringify(this.setting('safe_mode_extensions')()) ? app.data.safeModeExtensions : false;

    return (
      <FormSection label={app.translator.trans('core.admin.advanced.maintenance.section_label')}>
        <Form>
          {this.buildSettingComponent({
            type: 'select',
            help: app.translator.trans('core.admin.advanced.maintenance.help'),
            setting: 'maintenance_mode',
            refreshAfterSaving: true,
            options: {
              [MaintenanceMode.NO_MAINTENANCE]: app.translator.trans('core.admin.advanced.maintenance.options.0'),
              [MaintenanceMode.HIGH_MAINTENANCE]: {
                label: app.translator.trans('core.admin.advanced.maintenance.options.1'),
                disabled: true,
              },
              [MaintenanceMode.LOW_MAINTENANCE]: app.translator.trans('core.admin.advanced.maintenance.options.2'),
              [MaintenanceMode.SAFE_MODE]: app.translator.trans('core.admin.advanced.maintenance.options.3'),
            },
            default: 0,
          })}
          {parseInt(this.setting('maintenance_mode')()) === MaintenanceMode.SAFE_MODE
            ? this.buildSettingComponent({
                type: 'dropdown',
                label: app.translator.trans('core.admin.advanced.maintenance.safe_mode_extensions'),
                help: safeModeExtensionsByConfig
                  ? app.translator.trans('core.admin.advanced.maintenance.safe_mode_extensions_override_help', {
                      extensions: safeModeExtensionsByConfig.map((id) => app.data.extensions[id].extra['flarum-extension'].title).join(', '),
                    })
                  : null,
                setting: 'safe_mode_extensions',
                json: true,
                refreshAfterSaving: true,
                multiple: true,
                disabled: safeModeExtensionsByConfig,
                options: Object.entries(app.data.extensions).reduce((acc, [id, extension]) => {
                  // @ts-ignore
                  acc[id] = extension.extra['flarum-extension'].title;
                  return acc;
                }, {}),
              })
            : null}
          {app.data.maintenanceByConfig ? (
            <div className="Form-group">
              <label>{app.translator.trans('core.admin.advanced.maintenance.config_override.label')}</label>
              <p className="helpText">{app.translator.trans('core.admin.advanced.maintenance.config_override.help')}</p>
              <strong className="helpText">{app.translator.trans('core.admin.advanced.maintenance.options.' + app.data.maintenanceMode)}</strong>
            </div>
          ) : null}
        </Form>
      </FormSection>
    );
  }
}
