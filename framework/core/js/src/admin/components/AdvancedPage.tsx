import app from '../../admin/app';
import AdminPage from './AdminPage';
import type { IPageAttrs } from '../../common/components/Page';
import type Mithril from 'mithril';
import Form from '../../common/components/Form';
import extractText from '../../common/utils/extractText';
import FormSectionGroup, { FormSection } from './FormSectionGroup';

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
        <FormSectionGroup>
          <FormSection label={app.translator.trans('core.admin.advanced.search.section_label')}>
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
          </FormSection>
        </FormSectionGroup>

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
}
