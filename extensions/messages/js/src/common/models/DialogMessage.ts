import Model from 'flarum/common/Model';
import computed from 'flarum/common/utils/computed';
import { getPlainContent } from 'flarum/common/utils/string';
import type Dialog from './Dialog';
import type User from 'flarum/common/models/User';

export default class DialogMessage extends Model {
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

  dialog() {
    return Model.hasOne<Dialog>('dialog').call(this);
  }
  user() {
    return Model.hasOne<User>('user').call(this);
  }
}
