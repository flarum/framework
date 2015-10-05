import Component from 'flarum/Component';

export default class DashboardPage extends Component {
  view() {
    return (
      <div className="DashboardPage">
        <div className="container">
          <h2>Welcome to Flarum Beta</h2>
          <p>{app.trans('core.admin.dashboard_version_text', {version: <strong>{app.forum.attribute('version')}</strong>})}</p>
          <p>{app.trans('core.admin.dashboard_beta_warning_text', {strong: <strong/>})}</p>
          <ul>
            <li>{app.trans('core.admin.dashboard_contributing_text', {a: <a href="http://flarum.org/docs/contributing" target="_blank"/>})}</li>
            <li>{app.trans('core.admin.dashboard_troubleshooting_text', {a: <a href="http://flarum.org/docs/troubleshooting" target="_blank"/>})}</li>
            <li>{app.trans('core.admin.dashboard_support_text', {a: <a href="http://discuss.flarum.org/t/support" target="_blank"/>})}</li>
            <li>{app.trans('core.admin.dashboard_features_text', {a: <a href="http://discuss.flarum.org/t/features" target="_blank"/>})}</li>
            <li>{app.trans('core.admin.dashboard_extension_text', {a: <a href="http://flarum.org/docs/extend" target="_blank"/>})}</li>
          </ul>
        </div>
      </div>
    );
  }
}
