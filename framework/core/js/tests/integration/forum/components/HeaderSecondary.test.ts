import bootstrapForum from '@flarum/jest-config/src/boostrap/forum';
import HeaderSecondary from '../../../../src/forum/components/HeaderSecondary';
import { app } from '../../../../src/forum';
import mq from 'mithril-query';

beforeAll(() => bootstrapForum());

describe('HeaderSecondary', () => {
  beforeAll(() => app.boot());

  test('renders', () => {
    const header = mq(HeaderSecondary);

    expect(header).toBeTruthy();
  });
});
