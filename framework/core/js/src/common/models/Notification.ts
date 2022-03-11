import Model from '../Model';
import User from './User';

export default class Notification extends Model {
  contentType() {
    return Model.attribute<string>('contentType').call(this);
  }
  content() {
    return Model.attribute<string>('content').call(this);
  }
  createdAt() {
    return Model.attribute<Date, string>('createdAt', Model.transformDate).call(this);
  }

  isRead() {
    return Model.attribute<boolean>('isRead').call(this);
  }

  user() {
    return Model.hasOne<User>('user').call(this);
  }
  fromUser() {
    return Model.hasOne<User | null>('fromUser').call(this);
  }
  subject() {
    return Model.hasOne<Model | null>('subject').call(this);
  }
}
