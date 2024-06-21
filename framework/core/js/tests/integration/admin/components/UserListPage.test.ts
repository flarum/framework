import bootstrapAdmin from '@flarum/jest-config/src/boostrap/admin';
import UserListPage from '../../../../src/admin/components/UserListPage';
import { app } from '../../../../src/admin';
import mq from 'mithril-query';

beforeAll(() => bootstrapAdmin());

describe('UserListPage', () => {
  beforeAll(() => {
    app.boot();
  });

  test('it renders', () => {
    const page = mq(UserListPage);

    expect(page).toHaveElement('.UserListPage');
  });
});
