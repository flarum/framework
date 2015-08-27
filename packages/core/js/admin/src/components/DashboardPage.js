import Component from 'flarum/Component';

export default class DashboardPage extends Component {
  view() {
    return (
      <div className="DashboardPage">
        <div className="container">
          <h2>Welcome to Flarum Beta</h2>
          <p>Thanks for trying out Flarum! You are running version <strong>{app.forum.attribute('version')}</strong>.</p>
          <p>This is beta software, and should not be used in production. If you run into any trouble, please read the <a href="http://flarum.org/docs/troubleshooting" target="_blank">Troubleshooting docs</a>. You can get further help on the <a href="http://discuss.flarum.org" target="_blank">support forum</a> and report bugs on <a href="https://github.com/flarum/core/issues" target="_blank">GitHub</a>.</p>
          <p>If you'd like to contribute, check out the <a href="http://flarum.org/docs/contributing" target="_blank">Contributing page</a>.</p>
        </div>
      </div>
    );
  }
}
