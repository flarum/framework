import Model, { type ModelIdentifier } from 'flarum/common/Model';
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

  /**
   * The id of this dialog's first or last message, when it has one.
   *
   * A dialog can be left without either, for instance when the message one of
   * them pointed at was deleted, so every caller has to cope with the
   * relationship being absent rather than reading straight through it.
   */
  messageRelationshipId(name: 'firstMessage' | 'lastMessage'): string | undefined {
    return (this.data.relationships?.[name]?.data as ModelIdentifier | undefined)?.id;
  }

  unreadCount() {
    return Model.attribute<number>('unreadCount').call(this);
  }
  lastMessageId() {
    return Model.attribute<number>('lastMessageId').call(this);
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
