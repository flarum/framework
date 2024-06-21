import bootstrapAdmin from '@flarum/jest-config/src/boostrap/admin';
import AppearancePage from '../../../../src/admin/components/AppearancePage';
import { app } from '../../../../src/admin';
import mq from 'mithril-query';

beforeAll(() => bootstrapAdmin());

describe('AppearancePage', () => {
  beforeAll(() => {
    app.boot();
  });

  test('it renders', () => {
    const page = mq(AppearancePage);

    expect(page).toHaveElement('.AppearancePage');
  });
});
