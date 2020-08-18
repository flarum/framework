import Component from 'flarum/Component';
import LoadingIndicator from 'flarum/components/LoadingIndicator';
import avatar from 'flarum/helpers/avatar';
import username from 'flarum/helpers/username';
import icon from 'flarum/helpers/icon';
import humanTime from 'flarum/helpers/humanTime';

export default class FlagList extends Component {
  init() {
    this.state = this.props.state;
  }

  view() {
    const flags = this.state.cache;

    return (
      <div className="NotificationList FlagList">
        <div className="NotificationList-header">
          <h4 className="App-titleControl App-titleControl--text">{app.translator.trans('flarum-flags.forum.flagged_posts.title')}</h4>
        </div>
        <div className="NotificationList-content">
          <ul className="NotificationGroup-content">
            {flags.length
              ? flags.map(flag => {
                const post = flag.post();

                return (
                  <li>
                    <a href={app.route.post(post)} className="Notification Flag" config={function(element, isInitialized) {
                      m.route.apply(this, arguments);

                      if (!isInitialized) $(element).on('click', () => app.flags.index = post);
                    }}>
                      {avatar(post.user())}
                      {icon('fas fa-flag', {className: 'Notification-icon'})}
                      <span className="Notification-content">
                        {app.translator.trans('flarum-flags.forum.flagged_posts.item_text', {username: username(post.user()), em: <em/>, discussion: post.discussion().title()})}
                      </span>
                      {humanTime(flag.createdAt())}
                      <div className="Notification-excerpt">
                        {post.contentPlain()}
                      </div>
                    </a>
                  </li>
                );
              })
              : !this.state.loading
                ? <div className="NotificationList-empty">{app.translator.trans('flarum-flags.forum.flagged_posts.empty_text')}</div>
                : LoadingIndicator.component({className: 'LoadingIndicator--block'})}
          </ul>
        </div>
      </div>
    );
  }
}
