import bootstrapForum from '@flarum/jest-config/src/boostrap/forum';
import Navigation from '../../../../src/common/components/Navigation';
import { app } from '../../../../src/forum';
import mq from 'mithril-query';

beforeAll(() => bootstrapForum());

describe('Navigation', () => {
  beforeAll(() => app.boot());

  test('renders as normal nav', () => {
    const nav = mq(Navigation);

    expect(nav).toBeTruthy();
  });

  test('renders as drawer', () => {
    const nav = mq(Navigation, { drawer: true });

    expect(nav).toBeTruthy();
  });
});
