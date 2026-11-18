import Model from 'flarum/common/Model';
import computed from 'flarum/common/utils/computed';
import { getPlainContent } from 'flarum/common/utils/string';
import type Dialog from './Dialog';
import type User from 'flarum/common/models/User';

export default class DialogMessage extends Model {
  number() {
    return Model.attribute<number>('number').call(this);
  }
  /** 'comment' for what a member wrote; otherwise a notice, such as 'userLeft' or 'renamed'. */
  type() {
    return Model.attribute<string | undefined>('type').call(this);
  }
  /** What a notice says happened. */
  eventData() {
    return Model.attribute<Record<string, any> | undefined>('eventData').call(this) || {};
  }
  isComment(): boolean {
    return (this.type() ?? 'comment') === 'comment';
  }
  content() {
    return Model.attribute<string | null | undefined>('content').call(this);
  }
  contentHtml() {
    return Model.attribute<string | null | undefined>('contentHtml').call(this);
  }
  renderFailed() {
    return Model.attribute<boolean | undefined>('renderFailed').call(this);
  }
  contentPlain() {
    return computed<string | null | undefined>('contentHtml', (content) => {
      if (typeof content === 'string') {
        return getPlainContent(content);
      }

      return content as null | undefined;
    }).call(this);
  }
  createdAt() {
    return Model.attribute<Date, string>('createdAt', Model.transformDate).call(this);
  }
  /** When the message was last edited, or null if it never was. */
  editedAt() {
    return Model.attribute<Date | null | undefined, string | null>('editedAt', Model.transformDate).call(this);
  }
  /** Deleted, in a way that can be undone. Only its author, and who can restore it, get its content. */
  isHidden() {
    return !!Model.attribute<boolean | undefined>('isHidden').call(this);
  }
  hiddenAt() {
    return Model.attribute<Date | null | undefined, string | null>('hiddenAt', Model.transformDate).call(this);
  }
  /** Only present for those allowed to see it. */
  ipAddress() {
    return Model.attribute<string | null | undefined>('ipAddress').call(this);
  }

  dialog() {
    return Model.hasOne<Dialog>('dialog').call(this);
  }
  user() {
    return Model.hasOne<User>('user').call(this);
  }
  editedUser() {
    return Model.hasOne<User | null>('editedUser').call(this);
  }
  hiddenUser() {
    return Model.hasOne<User | null>('hiddenUser').call(this);
  }

  /** Delete for good. */
  canDelete() {
    return Model.attribute<boolean>('canDelete').call(this);
  }
  canEdit() {
    return Model.attribute<boolean | undefined>('canEdit').call(this);
  }
  /** Delete in a way that can be undone, and restore. */
  canHide() {
    return Model.attribute<boolean | undefined>('canHide').call(this);
  }

  /** With flarum/likes: some of who liked it, this member first when they did. */
  likes() {
    return Model.hasMany<User>('likes').call(this);
  }
  /** With flarum/likes. */
  likesCount() {
    return Model.attribute<number | undefined>('likesCount').call(this);
  }
  /** With flarum/likes. */
  canLike() {
    return Model.attribute<boolean | undefined>('canLike').call(this);
  }
}
