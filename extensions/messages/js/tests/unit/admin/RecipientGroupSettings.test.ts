import bootstrapAdmin from '@flarum/jest-config/src/bootstrap/admin';
import app from 'flarum/admin/app';
import mq from 'mithril-query';
import RecipientGroupSettings from '../../../src/admin/components/RecipientGroupSettings';

beforeAll(() => {
  bootstrapAdmin();
  app.boot();
  app.store.pushPayload({ data: [
    { type: 'groups', id: '2', attributes: { namePlural: 'Guests' } },
    { type: 'groups', id: '3', attributes: { namePlural: 'Members' } },
    { type: 'groups', id: '4', attributes: { namePlural: 'Moderators' } },
  ] } as any);
});

it('lets an admin enable a recipient Group and excludes the implicit audience groups', () => {
  let value = '[]';
  const view = mq(RecipientGroupSettings, { value, onchange: (next: string) => { value = next; } });
  view.should.contain('Moderators');
  view.should.not.contain('Guests');
  view.should.not.contain('Members');
  view.trigger('input[data-group="4"]', 'change');
  expect(JSON.parse(value)).toEqual([4]);
});
