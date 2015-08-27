import Component from 'flarum/Component';

export default class DashboardPage extends Component {
  view() {
    return (
      <div className="DashboardPage">
        <div className="container">
          <h2>Welcome to Flarum Beta</h2>
          <p>Thanks for trying out Flarum! You are running version <strong>{app.forum.attribute('version')}</strong>. This is beta software, and should not be used in production.</p>
          <ul>
            <li>Having problems? Read the <a href="http://flarum.org/docs/troubleshooting" target="_blank">Troubleshooting docs</a>.</li>
            <li>Found a bug? Please <a href="https://github.com/flarum/core/issues" target="_blank">report it on GitHub</a>.</li>
            <li>Got some feedback? Let us know what you think on the <a href="http://discuss.flarum.org" target="_blank">Support Forum</a>.</li>
            <li>Want to contribute? Read the <a href="http://flarum.org/docs/contributing" target="_blank">Contributing docs</a>.</li>
            <li>Interested in developing extensions? Read the <a href="http://flarum.org/docs/extend" target="_blank">Extension docs</a>.</li>
          </ul>
        </div>
      </div>
    );
  }
}
