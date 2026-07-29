import bootstrapAdmin from '@flarum/jest-config/src/bootstrap/admin';
import { app } from '../../../src/admin';

beforeAll(() => bootstrapAdmin());

describe('admin assets revision check', () => {
  beforeAll(() => app.boot());

  beforeEach(() => {
    app.alerts.clear();
    app.data.assetsRevision = 'boot-revision';
  });

  test('the admin app does not prompt to reload on a revision change', () => {
    // checkAssetsRevision is a no-op in the base Application; only the forum app overrides it.
    (app as any).checkAssetsRevision('a-newer-revision');

    expect(Object.keys(app.alerts.getActiveAlerts())).toHaveLength(0);
  });
});
