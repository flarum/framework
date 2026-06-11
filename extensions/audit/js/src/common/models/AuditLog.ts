import Model from 'flarum/common/Model';
import User from 'flarum/common/models/User';
import Discussion from 'flarum/common/models/Discussion';
import Post from 'flarum/common/models/Post';
import Tag from 'flarum/tags/common/models/Tag';

export default class AuditLog extends Model {
  actorId = Model.attribute<string>('actorId');
  client = Model.attribute<string>('client');
  ipAddress = Model.attribute<string | null>('ipAddress');
  action = Model.attribute<string>('action');
  payload = Model.attribute<{ [key: string]: any }>('payload');
  createdAt = Model.attribute('createdAt', Model.transformDate);

  actor = Model.hasOne<User>('actor');
  discussion = Model.hasOne<Discussion>('discussion');
  newDiscussion = Model.hasOne<Discussion>('newDiscussion');
  post = Model.hasOne<Post>('post');
  tag = Model.hasOne<Tag>('tag');
  user = Model.hasOne<User>('user');
}
