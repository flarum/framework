import bootstrapForum from '@flarum/jest-config/src/bootstrap/forum';
import app from 'flarum/forum/app';
import m from 'mithril';
import mq from 'mithril-query';
import Stream from 'flarum/common/utils/Stream';
import RecipientSelectionModal from '../../../../src/forum/components/RecipientSelectionModal';
import loadTranslations from '../../translations';

beforeAll(() => {
  bootstrapForum();
  app.boot();
  loadTranslations();
  app.store.pushPayload({ data: [
    { type: 'groups', id: '4', attributes: { namePlural: 'Moderators', canMessage: true } },
    { type: 'groups', id: '5', attributes: { namePlural: 'Hidden team', canMessage: false } },
  ] } as any);
});

it('lets a sender choose an allowed Group as the only recipient', () => {
  const modal = new (RecipientSelectionModal as any)();
  let submitted: string[] = [];
  modal.attrs = { selected: [], selectedGroups: [], onsubmit: () => {}, onsubmitGroups: (groups: any[]) => { submitted = groups.map((group) => group.id()); } };
  modal.selected = Stream([]);
  modal.selectedGroups = Stream([]);
  modal.search = Stream('');
  modal.results = Stream({ '': [] });
  modal.loading = false;
  const view = mq(m('div', modal.content()));
  view.should.contain('Moderators');
  view.should.not.contain('Hidden team');
  expect(modal.meetsRequirements()).toBe(false);
  view.trigger('.RecipientSelectionModal-groups input', 'change');
  expect(modal.meetsRequirements()).toBe(true);
  modal.hide = () => {};
  modal.onsubmit({ preventDefault() {} });
  expect(submitted).toEqual(['4']);
});

it('keeps a selected Group removable when the server disables it', () => {
  const group = app.store.getById('groups', '4')!;
  group.pushAttributes({ canMessage: false });
  app.store.getById('groups', '5')!.pushAttributes({ canMessage: true });
  const modal = new (RecipientSelectionModal as any)();
  modal.attrs = { selected: [], selectedGroups: [group], onsubmit: () => {}, onsubmitGroups: () => {} };
  modal.selected = Stream([]);
  modal.selectedGroups = Stream([group]);
  modal.search = Stream('');
  modal.results = Stream({ '': [] });
  modal.loading = false;
  const view = mq(m('div', modal.content()));
  view.should.contain('Moderators');
  expect(modal.meetsRequirements()).toBe(false);
  view.trigger('input[data-group="4"]', 'change');
  view.trigger('input[data-group="5"]', 'change');
  expect(modal.meetsRequirements()).toBe(true);
  expect(modal.selectedGroups().map((selected: any) => selected.id())).toEqual(['5']);
});
