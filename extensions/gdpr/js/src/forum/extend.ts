import Extend from 'flarum/common/extenders';
import ErasureRequestsPage from './components/ErasureRequestsPage';

import { default as extend } from '../common/extend';
import ExportAvailableNotification from './components/ExportAvailableNotification';

export default [
  ...extend,

  new Extend.Routes() //
    .add('erasure-requests', '/erasure-requests', ErasureRequestsPage),

  new Extend.Notification().add('gdprExportAvailable', ExportAvailableNotification),
];
