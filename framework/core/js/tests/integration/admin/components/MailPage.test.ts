import bootstrapAdmin from '@flarum/jest-config/src/boostrap/admin';
import MailPage from '../../../../src/admin/components/MailPage';
import { app } from '../../../../src/admin';
import mq from 'mithril-query';

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
