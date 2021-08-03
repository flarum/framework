import FieldSet from '../../common/components/FieldSet';
import ItemList from '../../common/utils/ItemList';
import AdminPage from './AdminPage';
import Alert from '../../common/components/Alert';

export default class AdvancedPage extends AdminPage {
  oninit(vnode) {
    super.oninit(vnode);

    this.queueDrivers = {};

    app.data.queueDrivers.forEach((driver) => {
      this.queueDrivers[driver] = app.translator.trans('core.admin.queue.' + driver);
    });
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
        {this.buildSettingComponent({
          type: 'select',
          setting: 'queue_driver',
          options: Object.keys(this.queueDrivers).reduce((memo, val) => ({ ...memo, [val]: val }), {}),
          label: app.translator.trans('core.admin.queue.driver_heading'),
          className: 'AdvancedPage-QueueSettings',
        })}
        {this.submitButton()}
      </div>,
    ];
  }
}
