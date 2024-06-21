import bootstrapAdmin from '@flarum/jest-config/src/boostrap/admin';
import PermissionsPage from '../../../../src/admin/components/PermissionsPage';
import { app } from '../../../../src/admin';
import mq from 'mithril-query';

beforeAll(() => bootstrapAdmin());

describe('PermissionsPage', () => {
  beforeAll(() => {
    app.boot();
  });

  test('it renders', () => {
    const page = mq(PermissionsPage);

    expect(page).toHaveElement('.PermissionsPage');
  });
});
