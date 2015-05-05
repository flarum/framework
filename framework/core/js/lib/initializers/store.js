import Store from 'flarum/store';
import User from 'flarum/models/user';
import Discussion from 'flarum/models/discussion';
import Post from 'flarum/models/post';
import Group from 'flarum/models/group';
import Activity from 'flarum/models/activity';
import Notification from 'flarum/models/notification';

export default function(app) {
  app.store = new Store();

  app.store.models['users'] = User;
  app.store.models['discussions'] = Discussion;
  app.store.models['posts'] = Post;
  app.store.models['groups'] = Group;
  app.store.models['activity'] = Activity;
  app.store.models['notifications'] = Notification;
};
