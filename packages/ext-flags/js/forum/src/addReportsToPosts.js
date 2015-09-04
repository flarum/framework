import { extend } from 'flarum/extend';
import app from 'flarum/app';
import CommentPost from 'flarum/components/CommentPost';
import Button from 'flarum/components/Button';
import punctuate from 'flarum/helpers/punctuate';
import username from 'flarum/helpers/username';
import ItemList from 'flarum/utils/ItemList';
import PostControls from 'flarum/utils/PostControls';

export default function() {
  extend(CommentPost.prototype, 'attrs', function(attrs) {
    if (this.props.post.reports().length) {
      attrs.className += ' Post--reported';
    }
  });

  CommentPost.prototype.dismissReport = function(data) {
    const post = this.props.post;

    delete post.data.relationships.reports;

    this.subtree.invalidate();

    if (app.cache.reports) {
      app.cache.reports.some((report, i) => {
        if (report.post() === post) {
          app.cache.reports.splice(i, 1);

          if (app.cache.reportIndex === post) {
            let next = app.cache.reports[i];

            if (!next) next = app.cache.reports[0];

            if (next) {
              const nextPost = next.post();
              app.cache.reportIndex = nextPost;
              m.route(app.route.post(nextPost));
            }
          }

          return true;
        }
      });
    }

    return app.request({
      url: app.forum.attribute('apiUrl') + post.apiEndpoint() + '/reports',
      method: 'DELETE',
      data
    });
  };

  CommentPost.prototype.reportActionItems = function() {
    const items = new ItemList();

    if (this.props.post.isHidden()) {
      if (this.props.post.canDelete()) {
        items.add('delete',
          <Button className="Button"
            icon="trash-o"
            onclick={() => {
              this.dismissReport().then(() => {
                PostControls.deleteAction.apply(this.props.post);
                m.redraw();
              });
            }}>
            Delete Forever
          </Button>,
          100
        );
      }
    } else {
      items.add('hide',
        <Button className="Button"
          icon="trash-o"
          onclick={() => {
            this.dismissReport().then(() => {
              PostControls.hideAction.apply(this.props.post);
              m.redraw();
            });
          }}>
          Delete Post
        </Button>,
        100
      );
    }

    items.add('dismiss', <Button className="Button Button--icon Button--link" icon="times" onclick={this.dismissReport.bind(this)}>Dismiss Report</Button>, -100);

    return items;
  };

  extend(CommentPost.prototype, 'content', function(vdom) {
    const post = this.props.post;
    const reports = post.reports();

    if (!reports.length) return;

    if (post.isHidden()) this.revealContent = true;

    const users = reports.map(report => {
      const user = report.user();

      return user
        ? <a href={app.route.user(user)} config={m.route}>{username(user)}</a>
        : report.reporter();
    });

    const usedReasons = [];
    const reasons = reports.map(report => report.reason()).filter(reason => {
      if (reason && usedReasons.indexOf(reason) === -1) {
        usedReasons.push(reason);
        return true;
      }
    });

    const details = reports.map(report => report.reasonDetail()).filter(detail => detail);

    vdom.unshift(
      <div className="Post-reported">
        <div className="Post-reported-summary">
          {app.trans(reasons.length ? 'reports.reported_by_with_reason' : 'reports.reported_by', {
            reasons: punctuate(reasons.map(reason => app.trans('reports.reason_' + reason, undefined, reason))),
            users: punctuate(users)
          })}
          {details.map(detail => <div className="Post-reported-detail">{detail}</div>)}
        </div>
        <div className="Post-reported-actions">
          {this.reportActionItems().toArray()}
        </div>
      </div>
    );
  });
}
