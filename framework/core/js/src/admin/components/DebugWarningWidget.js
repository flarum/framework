import app from '../../admin/app';
import icon from '../../common/helpers/icon';
import DashboardWidget from './DashboardWidget';

export default class DebugWarningWidget extends DashboardWidget {
  className() {
    return 'DebugWarningWidget';
  }

  content() {
    return (
      <div>
        <h4 className="DebugWarningWidget-title">
          {icon('fas fa-exclamation-triangle')} {app.translator.trans('core.admin.debug-warning.label')}
        </h4>
        <p className="DebugWarningWidget-detail">{app.translator.trans('core.admin.debug-warning.detail')}</p>
      </div>
    );
  }
}
