import app from 'flarum/forum/app';
import Application from './extend/Application';
import Discussion from './extend/Discussion';
import DiscussionList from './extend/DiscussionList';
import Post from './extend/Post';
import User from './extend/User';
import DialogList from './extend/DialogList';

export { default as extend } from './extend';

app.initializers.add('flarum-realtime', () => {
  Application();
  Discussion();
  DiscussionList();
  DialogList();
  Post();
  User();
});
