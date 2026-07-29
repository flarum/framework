import app from 'flarum/forum/app';
import NewActivity from './DiscussionList/NewActivity';
import IndexTyping from './DiscussionList/IndexTyping';

export default function DiscussionList() {
  if (!!app.data['flarum-realtime.release-discussion-updates']) {
    NewActivity();
  }

  if (!!app.data['flarum-realtime.index-typing-indicator']) {
    IndexTyping();
  }
}
