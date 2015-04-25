import Model from 'flarum/model';

class Group extends Model {}

Group.prototype.id = Model.prop('id');
Group.prototype.name = Model.prop('name');

export default Group;
