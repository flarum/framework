import Model from 'flarum/model';

class Group extends Model {}

Group.prototype.nameSingular = Model.attribute('nameSingular');
Group.prototype.namePlural = Model.attribute('namePlural');
Group.prototype.color = Model.attribute('color');
Group.prototype.icon = Model.attribute('icon');

export default Group;
