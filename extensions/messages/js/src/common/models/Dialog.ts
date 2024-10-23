import Model from 'flarum/common/Model';
import User from 'flarum/common/models/User';
import DialogMessage from './DialogMessage';
import app from 'flarum/common/app';

export default class Dialog extends Model {
  title() {
    return Model.attribute<string>('title').call(this);
  }
  type() {
    return Model.attribute<string>('type').call(this);
  }
  lastMessageAt() {
    return Model.attribute<Date, string>('lastMessageAt', Model.transformDate).call(this);
  }
  createdAt() {
    return Model.attribute<Date, string>('createdAt', Model.transformDate).call(this);
  }

  users() {
    return Model.hasMany<User>('users').call(this);
  }
  firstMessage() {
    return Model.hasOne<DialogMessage>('firstMessage').call(this);
  }
  lastMessage() {
    return Model.hasOne<DialogMessage>('lastMessage').call(this);
  }

  unreadCount() {
    return Model.attribute<number>('unreadCount').call(this);
  }
  lastReadMessageId() {
    return Model.attribute<number>('lastReadMessageId').call(this);
  }
  lastReadAt() {
    return Model.attribute<Date, string>('lastReadAt', Model.transformDate).call(this);
  }

  recipient() {
    let users = this.users();

    return !users ? null : users.find((user) => user && user.id() !== app.session.user!.id());
  }
}
