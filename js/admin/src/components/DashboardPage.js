import Component from 'flarum/Component';

export default class DashboardPage extends Component {
  view() {
    return (
      <div className="DashboardPage">
        <div className="container">
          <h2>Welcome to Flarum Beta</h2>
          <p>This is beta software; you shouldn't use it in production.</p>
          <p>You're running version X</p>
          <p>Get help on X. Report bugs here. Feedback here. Contribute here.</p>
        </div>
      </div>
    );
  }
}
