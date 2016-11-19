import Model from 'flarum/Model';

export default class Group extends Model {}

Object.assign(Group.prototype, {
  nameSingular: Model.attribute('nameSingular'),
  namePlural: Model.attribute('namePlural'),
  color: Model.attribute('color'),
  icon: Model.attribute('icon')
});

Group.ADMINISTRATOR_ID = '1';
Group.GUEST_ID = '2';
Group.MEMBER_ID = '3';
