import Extend from 'flarum/common/extenders';
import AuditLog from './models/AuditLog';

export default [
  new Extend.Store() //
    .add('audit', AuditLog),
];
