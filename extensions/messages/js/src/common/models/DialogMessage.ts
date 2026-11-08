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

  canDelete() {
    return Model.attribute<boolean>('canDelete').call(this);
  }
}
