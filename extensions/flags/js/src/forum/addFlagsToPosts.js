import { extend } from 'flarum/common/extend';
import app from 'flarum/forum/app';
import Post from 'flarum/forum/components/Post';
import Button from 'flarum/common/components/Button';
import ItemList from 'flarum/common/utils/ItemList';
import PostControls from 'flarum/forum/utils/PostControls';
import humanTime from 'flarum/common/utils/humanTime';

export default function () {
  extend(Post.prototype, 'elementAttrs', function (attrs) {
    if (this.attrs.post.flags().length) {
      attrs.className += ' Post--flagged';
    }
  });

  Post.prototype.dismissFlag = function (body) {
    const post = this.attrs.post;

    delete post.data.relationships.flags;

    this.subtree.invalidate();

    if (app.flags.cache) {
      app.flags.cache.some((flag, i) => {
        if (flag.post() === post) {
          app.flags.cache.splice(i, 1);

          if (app.flags.index === post) {
            let next = app.flags.cache[i];

            if (!next) next = app.flags.cache[0];

            if (next) {
              const nextPost = next.post();
              app.flags.index = nextPost;
              m.route.set(app.route.post(nextPost));
            }
          }

          return true;
        }
      });
    }

    return app.request({
      url: app.forum.attribute('apiUrl') + post.apiEndpoint() + '/flags',
      method: 'DELETE',
      body,
    });
  };

  Post.prototype.flagActionItems = function () {
    const items = new ItemList();

    const controls = PostControls.destructiveControls(this.attrs.post);

    Object.keys(controls.items).forEach((k) => {
      const attrs = controls.get(k).attrs;

      attrs.className = 'Button';

      extend(attrs, 'onclick', () => this.dismissFlag());
    });

    items.add('controls', <div className="ButtonGroup">{controls.toArray()}</div>);

    items.add(
      'dismiss',
      <Button className="Button" icon="far fa-eye-slash" onclick={this.dismissFlag.bind(this)}>
        {app.translator.trans('flarum-flags.forum.post.dismiss_flag_button')}
      </Button>,
      -100
    );

    return items;
  };

  extend(Post.prototype, 'content', function (vdom) {
    const post = this.attrs.post;
    const flags = post.flags();

    if (!flags.length) return;

    if (post.isHidden()) this.revealContent = true;

    vdom.unshift(
      <div className="Post-flagged">
        <div className="Post-flagged-flags">
          {flags.map((flag) => (
            <div className="Post-flagged-flag">{this.flagReason(flag)}</div>
          ))}
        </div>
        <div className="Post-flagged-actions">{this.flagActionItems().toArray()}</div>
      </div>
    );
  });

  Post.prototype.flagReason = function (flag) {
    if (flag.type() === 'user') {
      const user = flag.user();
      const reason = flag.reason() ? app.translator.trans(`flarum-flags.forum.flag_post.reason_${flag.reason()}_label`) : null;
      const detail = flag.reasonDetail();
      const time = humanTime(flag.createdAt());

      return [
        app.translator.trans(reason ? 'flarum-flags.forum.post.flagged_by_with_reason_text' : 'flarum-flags.forum.post.flagged_by_text', {
          time,
          user,
          reason,
        }),
        detail ? <span className="Post-flagged-detail">{detail}</span> : '',
      ];
    }
  };
}
