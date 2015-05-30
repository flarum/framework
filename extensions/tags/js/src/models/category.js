import Model from 'flarum/model';

class Category extends Model {}

Category.prototype.id = Model.prop('id');
Category.prototype.title = Model.prop('title');
Category.prototype.slug = Model.prop('slug');
Category.prototype.description = Model.prop('description');
Category.prototype.color = Model.prop('color');
Category.prototype.discussionsCount = Model.prop('discussionsCount');
Category.prototype.position = Model.prop('position');

export default Category;
