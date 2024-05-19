import bootstrapForum from '@flarum/jest-config/src/boostrap/forum';
import FieldSet from '../../../../src/common/components/FieldSet';
import m from 'mithril';
import mq from 'mithril-query';

beforeAll(() => bootstrapForum());

describe('FieldSet displays as expected', () => {
  it('renders', () => {
    const input = mq(
      m(
        FieldSet,
        {
          label: 'Test FieldSet',
          description: 'This is a test fieldset',
        },
        'child nodes'
      )
    );
    expect(input).toContainRaw('Test FieldSet');
    expect(input).toContainRaw('This is a test fieldset');
    expect(input).toContainRaw('child nodes');
  });
});
