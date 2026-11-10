import Model from 'flarum/common/Model';
import User from 'flarum/common/models/User';
import Discussion from 'flarum/common/models/Discussion';
import Post from 'flarum/common/models/Post';
import type Tag from 'ext:flarum/tags/common/models/Tag';

export default class AuditLog extends Model {
  actorId() {
    return Model.attribute<number | null>('actorId').call(this);
  }
  client() {
    return Model.attribute<string>('client').call(this);
  }
  ipAddress() {
    return Model.attribute<string | null>('ipAddress').call(this);
  }
  action() {
    return Model.attribute<string>('action').call(this);
  }
  payload() {
    return Model.attribute<{ [key: string]: any }>('payload').call(this);
  }
  createdAt() {
    return Model.attribute('createdAt', Model.transformDate).call(this);
  }

  actor() {
    return Model.hasOne<User>('actor').call(this);
  }
  discussion() {
    return Model.hasOne<Discussion>('discussion').call(this);
  }
  newDiscussion() {
    return Model.hasOne<Discussion>('newDiscussion').call(this);
  }
  post() {
    return Model.hasOne<Post>('post').call(this);
  }
  tag() {
    return Model.hasOne<Tag>('tag').call(this);
  }
  user() {
    return Model.hasOne<User>('user').call(this);
  }
}
