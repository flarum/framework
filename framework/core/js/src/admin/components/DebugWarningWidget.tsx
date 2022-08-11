import app from '../../admin/app';
import Alert from '../../common/components/Alert';
import Link from '../../common/components/Link';
import DashboardWidget from './DashboardWidget';

export default class DebugWarningWidget extends DashboardWidget {
  className() {
    return 'DebugWarningWidget';
  }

  content() {
    return (
      <Alert type="warning" dismissible={false} title={app.translator.trans('core.admin.debug-warning.label')} icon="fas fa-exclamation-triangle">
        {app.translator.trans('core.admin.debug-warning.detail', {
          link: <Link href="https://docs.flarum.org/troubleshoot/#step-0-activate-debug-mode" external={true} target="_blank" />,
        })}
      </Alert>
    );
  }
}
