import Model from '../Model';

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
  icon() {
    return Model.attribute<string | null>('icon').call(this);
  }

  isHidden() {
    return Model.attribute<boolean>('isHidden').call(this);
  }
}
