import Store from 'flarum/store';
import Forum from 'flarum/models/forum';
import User from 'flarum/models/user';
import Discussion from 'flarum/models/discussion';
import Post from 'flarum/models/post';
import Group from 'flarum/models/group';
import Activity from 'flarum/models/activity';
import Notification from 'flarum/models/notification';

export default function(app) {
  app.store = new Store();

  app.store.models = {
    forums: Forum,
    users: User,
    discussions: Discussion,
    posts: Post,
    groups: Group,
    activity: Activity,
    notifications: Notification
  };
}
