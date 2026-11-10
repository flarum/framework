import bootstrapAdmin from '@flarum/jest-config/src/bootstrap/admin';
import MailPage from '../../../../src/admin/components/MailPage';
import { app } from '../../../../src/admin';
import mq from 'mithril-query';
import { jest } from '@jest/globals';

beforeAll(() => bootstrapAdmin());

describe('MailPage', () => {
  beforeAll(() => {
    app.boot();
  });

  test('it renders', () => {
    const page = mq(MailPage);

    expect(page).toHaveElement('.MailPage');
  });
});


test('refresh synchronizes bounce capability for the dashboard and user list', async () => {
  const response = (available: boolean) => ({
    data: { attributes: { fields: {}, sending: true, errors: [], canReceiveWebhooks: available, canProvisionWebhooks: false, webhookUrl: null } },
  });
  const request = jest.spyOn(app, 'request').mockResolvedValue(response(true) as never);
  const page = new MailPage();
  app.forum.pushAttributes({ mailDriverSupportsBounceReporting: false });
  page.refresh();
  await Promise.resolve();
  expect(app.forum.attribute('mailDriverSupportsBounceReporting')).toBe(true);
  request.mockResolvedValue(response(false) as never);
  page.refresh();
  await Promise.resolve();
  expect(app.forum.attribute('mailDriverSupportsBounceReporting')).toBe(false);
  request.mockRestore();
});
