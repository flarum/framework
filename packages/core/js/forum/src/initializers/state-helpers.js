import Composer from 'flarum/components/composer';
import ReplyComposer from 'flarum/components/reply-composer';
import DiscussionPage from 'flarum/components/discussion-page';

export default function(app) {
  app.composingReplyTo = function(discussion) {
    return this.composer.component instanceof ReplyComposer &&
      this.composer.component.props.discussion === discussion &&
      this.composer.position() !== Composer.PositionEnum.HIDDEN;
  };

  app.viewingDiscussion = function(discussion) {
    return this.current instanceof DiscussionPage &&
      this.current.discussion() === discussion;
  };
};
