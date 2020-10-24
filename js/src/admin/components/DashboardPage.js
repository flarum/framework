import Page from '../../common/components/Page';
import StatusWidget from './StatusWidget';
import icon from '../../Common/helpers/icon';

export default class DashboardPage extends Page {
  view() {
    return (
      <div className="DashboardPage">
        <div className="DashboardPage-header">
          <div className="container">
            <h2>
              {icon('far fa-chart-bar')}
              {app.translator.trans('core.admin.dashboard.title')}
            </h2>
            <div className="helpText">{app.translator.trans('core.admin.dashboard.description')}</div>
          </div>
        </div>
        <div className="container">{this.availableWidgets()}</div>
      </div>
    );
  }

  availableWidgets() {
    return [<StatusWidget />];
  }
}
