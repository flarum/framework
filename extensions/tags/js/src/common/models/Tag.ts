import computed from 'flarum/common/utils/computed';
import Model from 'flarum/common/Model';
import withIconImage from 'flarum/common/utils/withIconImage';
import type Discussion from 'flarum/common/models/Discussion';

export default class Tag extends Model {
  name() {
    return Model.attribute<string>('name').call(this);
  }
  slug() {
    return Model.attribute<string>('slug').call(this);
  }
  storedSlug() {
    return Model.attribute<string>('storedSlug').call(this);
  }
  description() {
    return Model.attribute<string | null>('description').call(this);
  }

  color() {
    return Model.attribute<string | null>('color').call(this);
  }
  /**
   * The Font Awesome icon classes, with the classes that show the uploaded
   * image in their place when the tag has one. The stored classes alone are
   * `attribute('icon')`.
   */
  icon() {
    return withIconImage(Model.attribute<string | null>('icon').call(this), this.iconImageUrl(), this.iconImageSrcset());
  }
  iconImageUrl() {
    return Model.attribute<string | null>('iconImageUrl').call(this);
  }
  iconImageSrcset() {
    return Model.attribute<string | null>('iconImageSrcset').call(this);
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
  /**
   * Whether this is a child of a primary tag.
   */
  isChild() {
    return Model.attribute<boolean>('isChild').call(this);
  }
  isHidden() {
    return Model.attribute<boolean>('isHidden').call(this);
  }
  /**
   * Whether this is a primary tag: a top-level primary tag, or a child of one.
   *
   * Use this rather than checking `position()`. Secondary tags have no
   * position in 2.x, but that is an implementation detail which may change.
   */
  isPrimary() {
    return Model.attribute<boolean>('isPrimary').call(this);
  }
  /**
   * Whether this is a secondary tag.
   */
  isSecondary() {
    return !this.isPrimary();
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

  /**
   * Whether this is a primary tag without a parent.
   */
  isPrimaryParent() {
    return computed<boolean, this>('isPrimary', 'isChild', (isPrimary, isChild) => !!isPrimary && !isChild).call(this);
  }
}
