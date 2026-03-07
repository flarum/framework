import app from '@flarum/core/src/admin/app';
import AdminApplication from '@flarum/core/src/admin/AdminApplication';
import bootstrap from './common.js';

export default function bootstrapAdmin(payload = {}) {
  return bootstrap(AdminApplication, app, {
    extensions: {},
    settings: {},
    permissions: {},
    displayNameDrivers: [],
    slugDrivers: {},
    searchDrivers: {},
    modelStatistics: {
      users: 1,
    },
    ...payload,
  });
}
