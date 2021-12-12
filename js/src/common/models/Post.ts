import Model from '../Model';
import computed from '../utils/computed';
import { getPlainContent } from '../utils/string';
import Discussion from './Discussion';
import User from './User';

export default class Post extends Model {
  number() {
    return Model.attribute<number>('number').call(this);
  }
  discussion() {
    return Model.hasOne<Discussion>('discussion').call(this) as Discussion;
  }

  createdAt() {
    return Model.attribute<Date, string>('createdAt', Model.transformDate).call(this);
  }
  user() {
    return Model.hasOne<User>('user').call(this);
  }

  contentType() {
    return Model.attribute<string | null>('contentType').call(this);
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

  editedAt() {
    return Model.attribute('editedAt', Model.transformDate).call(this);
  }
  editedUser() {
    return Model.hasOne<User | null>('editedUser').call(this);
  }
  isEdited() {
    return computed<boolean>('editedAt', (editedAt) => !!editedAt).call(this);
  }

  hiddenAt() {
    return Model.attribute('hiddenAt', Model.transformDate).call(this);
  }
  hiddenUser() {
    return Model.hasOne<User | null>('hiddenUser').call(this);
  }
  isHidden() {
    return computed<boolean>('hiddenAt', (hiddenAt) => !!hiddenAt).call(this);
  }

  canEdit() {
    return Model.attribute<boolean | undefined>('canEdit').call(this);
  }
  canHide() {
    return Model.attribute<boolean | undefined>('canHide').call(this);
  }
  canDelete() {
    return Model.attribute<boolean | undefined>('canDelete').call(this);
  }
}
