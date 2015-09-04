import Component from 'flarum/Component';
import LoadingIndicator from 'flarum/components/LoadingIndicator';
import avatar from 'flarum/helpers/avatar';
import username from 'flarum/helpers/username';
import icon from 'flarum/helpers/icon';
import humanTime from 'flarum/helpers/humanTime';

export default class ReportList extends Component {
  constructor(...args) {
    super(...args);

    /**
     * Whether or not the notifications are loading.
     *
     * @type {Boolean}
     */
    this.loading = false;
  }

  view() {
    const reports = app.cache.reports || [];

    return (
      <div className="NotificationList ReportList">
        <div className="NotificationList-header">
          <h4 className="App-titleControl App-titleControl--text">Reported Posts</h4>
        </div>
        <div className="NotificationList-content">
          <ul className="NotificationGroup-content">
            {reports.length
              ? reports.map(report => {
                const post = report.post();

                return (
                  <li>
                    <a href={app.route.post(post)} className="Notification Report" config={function(element, isInitialized) {
                      m.route.apply(this, arguments);

                      if (!isInitialized) $(element).on('click', () => app.cache.reportIndex = post);
                    }}>
                      {avatar(post.user())}
                      {icon('flag', {className: 'Notification-icon'})}
                      <span className="Notification-content">
                        {username(post.user())} in <em>{post.discussion().title()}</em>
                      </span>
                      {humanTime(report.time())}
                      <div className="Notification-excerpt">
                        {post.contentPlain()}
                      </div>
                    </a>
                  </li>
                );
              })
              : !this.loading
                ? <div className="NotificationList-empty">{app.trans('reports.no_reports')}</div>
                : LoadingIndicator.component({className: 'LoadingIndicator--block'})}
          </ul>
        </div>
      </div>
    );
  }

  /**
   * Load reports into the application's cache if they haven't already
   * been loaded.
   */
  load() {
    if (app.cache.reports && !app.forum.attribute('unreadReportsCount')) {
      return;
    }

    this.loading = true;
    m.redraw();

    app.store.find('reports').then(reports => {
      app.forum.pushAttributes({unreadReportsCount: 0});
      app.cache.reports = reports.sort((a, b) => b.time() - a.time());

      this.loading = false;
      m.redraw();
    });
  }
}
