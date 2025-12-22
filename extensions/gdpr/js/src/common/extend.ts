import Extend from 'flarum/common/extenders';
import User from 'flarum/common/models/User';
import ErasureRequest from './models/ErasureRequest';
import Export from './models/Export';

export default [
  new Extend.Store() //
    .add('user-erasure-requests', ErasureRequest)
    .add('gdpr-exports', Export),

  new Extend.Model(User) //
    .attribute<boolean>('canModerateExports')
    .attribute<boolean>('anonymized')
    .hasOne<ErasureRequest>('erasureRequest'),
];
