import Model from '../Model';
import withIconImage from '../utils/withIconImage';

export default class Group extends Model {
  static ADMINISTRATOR_ID = '1';
  static GUEST_ID = '2';
  static MEMBER_ID = '3';

  nameSingular() {
    return Model.attribute<string>('nameSingular').call(this);
  }
  namePlural() {
    return Model.attribute<string>('namePlural').call(this);
  }

  color() {
    return Model.attribute<string | null>('color').call(this);
  }
  /**
   * The Font Awesome icon classes, with the classes that show the uploaded
   * image in their place when the group has one. The stored classes alone are
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

  isHidden() {
    return Model.attribute<boolean>('isHidden').call(this);
  }

  position() {
    return Model.attribute<number>('position').call(this);
  }
}
