import FieldSet from '../../common/components/FieldSet';
import ItemList from '../../common/utils/ItemList';
import AdminPage from './AdminPage';
import Alert from "../../common/components/Alert";

export default class AdvancedPage extends AdminPage {
  oninit(vnode) {
    super.oninit(vnode);

    this.queueDrivers = app.data.queueDrivers ?? [];
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
          type: 'text',
          setting: 'mail_from',
          label: app.translator.trans('core.admin.advanced.queue_driver_heading'),
          className: 'AdvancedPage-QueueSettings',
        })}
        {this.buildSettingComponent({
          type: 'select',
          setting: 'queue_driver',
          options: Object.keys(this.driverFields).reduce((memo, val) => ({ ...memo, [val]: val }), {}),
          label: app.translator.trans('core.admin.queue.driver_heading'),
          className: 'AdvancedPage-QueueSettings',
        })}
        {this.status.sending ||
        Alert.component(
          {
            dismissible: false,
          },
          app.translator.trans('core.admin.email.not_sending_message')
        )}
        {this.submitButton()}
      </div>,
    ];
  }
}
