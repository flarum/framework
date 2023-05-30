import app from 'flarum/forum/app';
import Component from 'flarum/common/Component';
import Link from 'flarum/common/components/Link';
import LoadingIndicator from 'flarum/common/components/LoadingIndicator';
import avatar from 'flarum/common/helpers/avatar';
import username from 'flarum/common/helpers/username';
import icon from 'flarum/common/helpers/icon';
import humanTime from 'flarum/common/helpers/humanTime';

export default class FlagList extends Component {
  oninit(vnode) {
    super.oninit(vnode);
    this.state = this.attrs.state;
  }

  view() {
    const flags = this.state.cache || [];

    return (
      <div className="NotificationList FlagList">
        <div className="NotificationList-header">
          <h4 className="App-titleControl App-titleControl--text">{app.translator.trans('flarum-flags.forum.flagged_posts.title')}</h4>
        </div>
        <div className="NotificationList-content">
          <ul className="NotificationGroup-content">
            {flags.length ? (
              flags.map((flag) => {
                const post = flag.post();

                return (
                  <li>
                    <Link
                      href={app.route.post(post)}
                      className="Notification Flag"
                      onclick={(e) => {
                        app.flags.index = post;
                        e.redraw = false;
                      }}
                    >
                      {avatar(post.user())}
                      {icon('fas fa-flag', { className: 'Notification-icon' })}
                      <span className="Notification-content">
                        {app.translator.trans('flarum-flags.forum.flagged_posts.item_text', {
                          username: username(post.user()),
                          em: <em />,
                          discussion: post.discussion().title(),
                        })}
                      </span>
                      {humanTime(flag.createdAt())}
                      <div className="Notification-excerpt">{post.contentPlain()}</div>
                    </Link>
                  </li>
                );
              })
            ) : !this.state.loading ? (
              <div className="NotificationList-empty">{app.translator.trans('flarum-flags.forum.flagged_posts.empty_text')}</div>
            ) : (
              <LoadingIndicator className="LoadingIndicator--block" />
            )}
          </ul>
        </div>
      </div>
    );
  }
}
