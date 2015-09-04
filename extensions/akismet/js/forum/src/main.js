import { extend } from 'flarum/extend';
import app from 'flarum/app';

import Button from 'flarum/components/Button';
import CommentPost from 'flarum/components/CommentPost';
import PostControls from 'flarum/utils/PostControls';

app.initializers.add('akismet', () => {
  extend(CommentPost.prototype, 'reportActionItems', function(items) {
    if (this.props.post.reports()[0].reporter() === 'Akismet') {
      items.add('notSpam',
        <Button className="Button"
          icon="check"
          onclick={() => {
            this.dismissReport({akismet: 'ham'}).then(() => {
              PostControls.restoreAction.apply(this.props.post);
              m.redraw();
            });
          }}>
          Not Spam
        </Button>
      );
    }
  });
}, -10); // set initializer priority to run after reports
