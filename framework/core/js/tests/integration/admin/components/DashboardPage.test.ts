import bootstrapAdmin from '@flarum/jest-config/src/boostrap/admin';
import DashboardPage from '../../../../src/admin/components/DashboardPage';
import { app } from '../../../../src/admin';
import mq from 'mithril-query';

beforeAll(() => bootstrapAdmin());

describe('DashboardPage', () => {
  beforeAll(() => {
    app.boot();
  });

  test('it renders', () => {
    const page = mq(DashboardPage);

    expect(page).toHaveElement('.DashboardPage');
  });
});
