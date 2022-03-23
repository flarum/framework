import Model from 'flarum/common/Model';
import type Post from 'flarum/common/models/Post';
import type User from 'flarum/common/models/User';

export default class Flag extends Model {
  type() {
    return Model.attribute<string>('type').call(this);
  }
  reason() {
    return Model.attribute<string | null>('reason').call(this);
  }
  reasonDetail() {
    return Model.attribute<string | null>('reasonDetail').call(this);
  }
  createdAt() {
    return Model.attribute('createdAt', Model.transformDate).call(this);
  }

  post() {
    return Model.hasOne<Post>('post').call(this);
  }
  user() {
    return Model.hasOne<User | null>('user').call(this);
  }
}
