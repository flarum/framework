import Model from '../Model';
import type { FieldDescriptor, FilterDescriptor, ResourceDescriptor } from '../resource/types';

/**
 * A `resource-schemas` record: the descriptor of one resource type, built for the current actor.
 * Its id is the described type.
 *
 * @experimental
 */
export default class ResourceSchema extends Model {
  schemaVersion() {
    return Model.attribute<number>('schemaVersion').call(this);
  }
  endpoint() {
    return Model.attribute<string>('endpoint').call(this);
  }
  capabilities() {
    return Model.attribute<ResourceDescriptor['capabilities']>('capabilities').call(this);
  }
  pagination() {
    return Model.attribute<ResourceDescriptor['pagination']>('pagination').call(this);
  }
  defaultSort() {
    return Model.attribute<string | undefined>('defaultSort').call(this);
  }
  search() {
    return Model.attribute<boolean | undefined>('search').call(this);
  }
  sorts() {
    return Model.attribute<string[] | undefined>('sorts').call(this);
  }
  filters() {
    return Model.attribute<FilterDescriptor[] | undefined>('filters').call(this);
  }
  fields() {
    return Model.attribute<FieldDescriptor[]>('fields').call(this);
  }
}
