import bootstrapAdmin from '@flarum/jest-config/src/boostrap/admin';
import BasicsPage from '../../../../src/admin/components/BasicsPage';
import { app } from '../../../../src/admin';
import mq from 'mithril-query';

beforeAll(() => bootstrapAdmin());

describe('BasicsPage', () => {
  beforeAll(() => {
    app.boot();
  });

  test('it renders', () => {
    const page = mq(BasicsPage);

    expect(page).toHaveElement('.BasicsPage');
  });
});
