import Model from 'flarum/common/Model';
import User from 'flarum/common/models/User';

export default class Export extends Model {
  file() {
    return Model.attribute<string>('file').call(this);
  }

  createdAt() {
    return Model.attribute('createdAt', Model.transformDate);
  }

  destroysAt() {
    return Model.attribute('destroysAt', Model.transformDate);
  }

  user() {
    return Model.hasOne<User>('user');
  }

  actor() {
    return Model.hasOne<User>('actor');
  }
}
