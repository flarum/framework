import bootstrapForum from '@flarum/jest-config/src/bootstrap/forum';
import { app } from '../../../src/forum';

beforeAll(() => bootstrapForum());

describe('assets revision check', () => {
  beforeAll(() => app.boot());

  const check = (serverRevision: string | null) => (app as any).checkAssetsRevision(serverRevision);

  /** Number of alerts currently shown. */
  const alertCount = () => Object.keys(app.alerts.getActiveAlerts()).length;

  beforeEach(() => {
    app.alerts.clear();
    app.data.assetsRevision = 'boot-revision';
    (app as any).assetsRevisionAlertShown = false;
  });

  test('shows a reload alert when the server revision differs from the booted one', () => {
    check('a-newer-revision');

    expect(alertCount()).toBe(1);

    // The alert offers a control (the reload button).
    const alert = Object.values(app.alerts.getActiveAlerts())[0];
    expect(alert.attrs.controls).toBeTruthy();
  });

  test('does nothing when the server revision matches the booted one', () => {
    check('boot-revision');

    expect(alertCount()).toBe(0);
  });

  test('does nothing when the server sends no revision header', () => {
    check(null);

    expect(alertCount()).toBe(0);
  });

  test('does nothing when the page booted without a revision', () => {
    delete app.data.assetsRevision;

    check('a-newer-revision');

    expect(alertCount()).toBe(0);
  });

  test('alerts at most once even across several differing responses', () => {
    check('a-newer-revision');
    check('an-even-newer-revision');
    check('yet-another-revision');

    expect(alertCount()).toBe(1);
  });
});
