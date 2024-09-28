import bootstrapAdmin from '@flarum/jest-config/src/boostrap/admin';
import AdvancedPage from '../../../../src/admin/components/AdvancedPage';
import { app } from '../../../../src/admin';
import mq from 'mithril-query';

beforeAll(() => bootstrapAdmin());

describe('AdvancedPage', () => {
  beforeAll(() => {
    app.boot();
  });

  test('it renders', () => {
    const page = mq(AdvancedPage);

    expect(page).toHaveElement('.AdvancedPage');
  });
});
