import Model from 'flarum/common/Model';

export default class DataType extends Model {
  type() {
    return Model.attribute<string>('type').call(this);
  }

  exportDescription() {
    return Model.attribute<string>('exportDescription').call(this);
  }

  anonymizeDescription() {
    return Model.attribute<string>('anonymizeDescription').call(this);
  }

  deleteDescription() {
    return Model.attribute<string>('deleteDescription').call(this);
  }

  extension() {
    return Model.attribute<string | null>('extension').call(this);
  }
}
