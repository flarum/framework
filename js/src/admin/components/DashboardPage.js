import Page from '../../common/components/Page';
import StatusWidget from "./StatusWidget";
import AdminHeader from "./AdminHeader";

export default class DashboardPage extends Page {
  view() {
    return (
      <div className="DashboardPage">
        {AdminHeader.component({
            icon: 'fas fa-chart-bar',
            description: app.translator.trans('core.admin.dashboard.description'),
            className: 'DashboardPage-header'
          }, app.translator.trans('core.admin.dashboard.title')
        )}
        <div className="container">{this.availableWidgets()}</div>
      </div>
    );
  }

  availableWidgets() {
    return [<StatusWidget />];
  }
}
