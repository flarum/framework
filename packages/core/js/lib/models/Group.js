import Model from 'flarum/Model';
import mixin from 'flarum/utils/mixin';

class Group extends mixin(Model, {
  nameSingular: Model.attribute('nameSingular'),
  namePlural: Model.attribute('namePlural'),
  color: Model.attribute('color'),
  icon: Model.attribute('icon')
}) {}

Group.ADMINISTRATOR_ID = '1';
Group.GUEST_ID = '2';
Group.MEMBER_ID = '3';

export default Group;
