import Model from '../Model';

export default class Group extends Model {
  static ADMINISTRATOR_ID = '1';
  static GUEST_ID = '2';
  static MEMBER_ID = '3';

  nameSingular = Model.attribute('nameSingular') as () => string;
  namePlural = Model.attribute('namePlural') as () => string;
  color = Model.attribute('color') as () => string;
  icon = Model.attribute('icon') as () => string;
}
