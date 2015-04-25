import Store from 'flarum/store';
import User from 'flarum/models/user';
import Discussion from 'flarum/models/discussion';
import Post from 'flarum/models/post';
import Group from 'flarum/models/group';
import Activity from 'flarum/models/activity';
import Notification from 'flarum/models/notification';

export default function(app) {
  app.store = new Store();

  app.store.model('users', User);
  app.store.model('discussions', Discussion);
  app.store.model('posts', Post);
  app.store.model('groups', Group);
  app.store.model('activity', Activity);
  app.store.model('notifications', Notification);
};
