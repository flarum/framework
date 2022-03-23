import computed from 'flarum/common/utils/computed';
import Model from 'flarum/common/Model';
import type Discussion from 'flarum/common/models/Discussion';

export default class Tag extends Model {
  name() {
    return Model.attribute<string>('name').call(this);
  }
  slug() {
    return Model.attribute<string>('slug').call(this);
  }
  description() {
    return Model.attribute<string | null>('description').call(this);
  }

  color() {
    return Model.attribute<string | null>('color').call(this);
  }
  backgroundUrl() {
    return Model.attribute<string | null>('backgroundUrl').call(this);
  }
  backgroundMode() {
    return Model.attribute<string | null>('backgroundMode').call(this);
  }
  icon() {
    return Model.attribute<string | null>('icon').call(this);
  }

  position() {
    return Model.attribute<number | null>('position').call(this);
  }
  parent() {
    return Model.hasOne<Tag | null>('parent').call(this);
  }
  children() {
    return Model.hasMany<Tag>('children').call(this);
  }
  defaultSort() {
    return Model.attribute<string | null>('defaultSort').call(this);
  }
  isChild() {
    return Model.attribute<boolean>('isChild').call(this);
  }
  isHidden() {
    return Model.attribute<boolean>('isHidden').call(this);
  }

  discussionCount() {
    return Model.attribute<number>('discussionCount').call(this);
  }
  lastPostedAt() {
    return Model.attribute('lastPostedAt', Model.transformDate).call(this);
  }
  lastPostedDiscussion() {
    return Model.hasOne<Discussion | null>('lastPostedDiscussion').call(this);
  }

  isRestricted() {
    return Model.attribute<boolean>('isRestricted').call(this);
  }
  canStartDiscussion() {
    return Model.attribute<boolean>('canStartDiscussion').call(this);
  }
  canAddToDiscussion() {
    return Model.attribute<boolean>('canAddToDiscussion').call(this);
  }

  isPrimary() {
    return computed<boolean, this>('position', 'parent', (position, parent) => position !== null && parent === false).call(this);
  }
}
