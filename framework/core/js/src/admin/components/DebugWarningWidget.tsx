import app from '../../admin/app';
import Alert from '../../common/components/Alert';
import DashboardWidget from './DashboardWidget';

export default class DebugWarningWidget extends DashboardWidget {
  className() {
    return 'DebugWarningWidget';
  }

  content() {
    return (
      <Alert type="warning" dismissible={false} title={app.translator.trans('core.admin.debug-warning.label')} icon="fas fa-exclamation-triangle">
        {app.translator.trans('core.admin.debug-warning.detail')}
      </Alert>
    );
  }
}
