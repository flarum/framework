import FieldSet from '../../common/components/FieldSet';
import ItemList from '../../common/utils/ItemList';
import AdminPage from './AdminPage';

export default class AdvancedPage extends AdminPage {
  oninit(vnode) {
    super.oninit(vnode);

    this.queueOptions = [];
  }

  headerInfo() {
    return {
      className: 'AdvancedPage',
      icon: 'fas fa-rocket',
      title: app.translator.trans('core.admin.advanced.title'),
      description: app.translator.trans('core.admin.advanced.description'),
    };
  }

  content() {
    return [
      <div className="Form">
        {Object.keys(this.queueOptions).length > 1
          ? [
              this.buildSettingComponent({
                type: 'select',
                setting: 'default_locale',
                options: this.localeOptions,
                label: app.translator.trans('core.admin.advanced.queue_driver_heading'),
              }),
            ]
          : ''}

        {this.submitButton()}
      </div>,
    ];
  }
}
