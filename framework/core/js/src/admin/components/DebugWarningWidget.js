import app from '../../admin/app';
import Alert from '../../common/components/Alert';
import icon from '../../common/helpers/icon';
import DashboardWidget from './DashboardWidget';

export default class DebugWarningWidget extends DashboardWidget {
  className() {
    return 'DebugWarningWidget';
  }

  content() {
    return (
      <Alert type="warning" dismissible={false}>
        <div>
          <h4 className="DebugWarningWidget-title">
            {icon('fas fa-exclamation-triangle')} {app.translator.trans('core.admin.debug-warning.label')}
          </h4>
        </div>
        <p className="DebugWarningWidget-detail">{app.translator.trans('core.admin.debug-warning.detail')}</p>
      </Alert>
    );
  }
}
