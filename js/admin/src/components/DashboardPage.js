import Component from 'flarum/Component';

export default class DashboardPage extends Component {
  view() {
    return (
      <div className="DashboardPage">
        <div className="container">
          <h2>Welcome to Flarum Beta</h2>
          <p>Thanks for trying out Flarum! You are running version <strong>{app.forum.attribute('version')}</strong>.</p>
          <p>This <strong>beta software</strong> is provided primarily so that you can help us test it and make it better; it should not be used in production.</p>
          <ul>
            <li>Want to look for bugs and contribute? Read the <a href="http://flarum.org/docs/contributing" target="_blank">Contributing docs</a>.</li>
            <li>Having problems? Follow the instructions in the <a href="http://flarum.org/docs/troubleshooting" target="_blank">Troubleshooting docs</a>.</li>
            <li>Found a bug? Please report it in our forum, under the <a href="http://discuss.flarum.org/t/support" target="_blank">Support tag</a>.</li>
            <li>Got an idea to improve a feature? Tell us about it under the <a href="http://discuss.flarum.org/t/features" target="_blank">Features tag</a>.</li>
            <li>Interested in developing extensions? Read the <a href="http://flarum.org/docs/extend" target="_blank">Extension docs</a>.</li>
          </ul>
        </div>
      </div>
    );
  }
}
