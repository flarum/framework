import Model from 'flarum/model';

class Group extends Model {}

Group.prototype.id = Model.prop('id');
Group.prototype.nameSingular = Model.prop('nameSingular');
Group.prototype.namePlural = Model.prop('namePlural');
Group.prototype.color = Model.prop('color');
Group.prototype.icon = Model.prop('icon');

export default Group;
