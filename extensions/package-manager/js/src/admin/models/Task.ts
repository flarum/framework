import Model from 'flarum/common/Model';

export type TaskOperations =
  | 'extension_install'
  | 'extension_remove'
  | 'extension_update'
  | 'update_global'
  | 'update_minor'
  | 'update_major'
  | 'update_check'
  | 'why_not';

export default class Task extends Model {
  status() {
    return Model.attribute<'pending' | 'running' | 'failure' | 'success'>('status').call(this);
  }

  operation() {
    return Model.attribute<TaskOperations>('operation').call(this);
  }

  command() {
    return Model.attribute<string>('command').call(this);
  }

  package() {
    return Model.attribute<string>('package').call(this);
  }

  output() {
    return Model.attribute<string>('output').call(this);
  }

  createdAt() {
    return Model.attribute('createdAt', Model.transformDate).call(this);
  }

  startedAt() {
    return Model.attribute<Date, string>('startedAt', Model.transformDate).call(this);
  }

  finishedAt() {
    return Model.attribute<Date, string>('finishedAt', Model.transformDate).call(this);
  }
}
