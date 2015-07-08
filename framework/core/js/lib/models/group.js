import Model from 'flarum/model';

class Group extends Model {}

Group.prototype.nameSingular = Model.attribute('nameSingular');
Group.prototype.namePlural = Model.attribute('namePlural');
Group.prototype.color = Model.attribute('color');
Group.prototype.icon = Model.attribute('icon');

Group.ADMINISTRATOR_ID = 1;
Group.GUEST_ID = 2;
Group.MEMBER_ID = 3;

export default Group;
