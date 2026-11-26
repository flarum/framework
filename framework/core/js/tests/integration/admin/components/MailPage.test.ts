import bootstrapAdmin from '@flarum/jest-config/src/bootstrap/admin';
import MailPage from '../../../../src/admin/components/MailPage';
import { app } from '../../../../src/admin';
import mq from 'mithril-query';
import { jest } from '@jest/globals';
import RequestError from '../../../../src/common/utils/RequestError';

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

describe('saving settings', () => {
  beforeAll(() => {
    app.boot();
  });

  const submit = () => ({ preventDefault() {} }) as never;
  const mailSettings = {
    data: { attributes: { fields: {}, sending: true, errors: [], canReceiveWebhooks: false, canProvisionWebhooks: false, webhookUrl: null } },
  };

  test('keeps the validation alert of a rejected save visible', async () => {
    const message = 'The mail host field must not be greater than 65000 characters.';
    const request = jest.spyOn(m, 'request').mockImplementation(((options: any) =>
      options.method === 'POST'
        ? Promise.reject(new RequestError(422, JSON.stringify({ errors: [{ detail: message }] }), options, {} as XMLHttpRequest))
        : Promise.resolve(mailSettings)) as never);
    const page = new MailPage();
    page.setting('mail_host')('host');

    await page.saveSettings(submit());

    const content = JSON.stringify(Object.values(app.alerts.getActiveAlerts()).map((alert) => [alert.attrs.type, alert.children]));
    expect(content).toContain('error');
    expect(content).toContain(message);
    expect(page.loading).toBe(false);
    expect(request).toHaveBeenCalledTimes(1);
    request.mockRestore();
  });

  test('refreshes after a successful save', async () => {
    const request = jest.spyOn(app, 'request').mockResolvedValue({} as never);
    const page = new MailPage();
    const refresh = jest.spyOn(page, 'refresh').mockImplementation(() => {});

    await page.saveSettings(submit());

    expect(refresh).toHaveBeenCalledTimes(1);
    request.mockRestore();
  });
});
