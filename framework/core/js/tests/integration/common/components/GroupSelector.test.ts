import bootstrapForum from '@flarum/jest-config/src/bootstrap/forum';
import GroupSelector from '../../../../src/common/components/GroupSelector';
import Group from '../../../../src/common/models/Group';
import app from '../../../../src/forum/app';
import m from 'mithril';
import mq from 'mithril-query';
import { jest } from '@jest/globals';

beforeAll(() => {
  bootstrapForum();

  app.store.pushPayload({
    data: ['1', '2', '3', '4'].map((id) => ({
      type: 'groups',
      id,
      attributes: { nameSingular: `Single ${id}`, namePlural: `Plural ${id}`, color: '#123456', icon: 'fas fa-user', isHidden: false },
    })),
  } as any);
});

const optionLabels = (selector: any) => selector.find('option').map((option: any) => option.textContent);

describe('GroupSelector', () => {
  it('offers every group in the store by name, minus the excluded ones', () => {
    const selector = mq(m(GroupSelector, { value: '', onchange: () => {}, exclude: [Group.GUEST_ID, Group.MEMBER_ID] }));

    expect(selector).toHaveElement('select.Select-input');
    expect(optionLabels(selector)).toEqual(['Choose a group', 'Plural 1', 'Plural 4']);
  });

  it('shows a placeholder until a group is chosen, then drops it', () => {
    const selector = mq(
      m(GroupSelector, { value: '', onchange: () => {}, placeholder: 'Pick one', groups: [app.store.getById<Group>('groups', '4')!] })
    );

    expect(selector).toHaveElement('option[disabled]');
    expect(optionLabels(selector)).toEqual(['Pick one', 'Plural 4']);

    const chosen = mq(m(GroupSelector, { value: '4', onchange: () => {}, groups: [app.store.getById<Group>('groups', '4')!] }));

    expect(chosen).not.toHaveElement('option[disabled]');
  });

  it('keeps the order of the groups it is given', () => {
    const groups = ['4', '1'].map((id) => app.store.getById<Group>('groups', id)!);
    const selector = mq(m(GroupSelector, { value: '', onchange: () => {}, groups }));

    expect(optionLabels(selector).slice(1)).toEqual(['Plural 4', 'Plural 1']);
  });

  it('reports the chosen group id', () => {
    const onchange = jest.fn();
    const selector = mq(m(GroupSelector, { value: '', onchange, exclude: [Group.GUEST_ID, Group.MEMBER_ID] }));

    selector.setValue('select', '4');

    expect(onchange).toHaveBeenCalledWith('4');
  });

  it('toggles ids in and out of an array when multiple', () => {
    const onchange = jest.fn();
    const selector = mq(GroupSelector, {
      value: ['1'],
      onchange,
      multiple: true,
      groups: [app.store.getById<Group>('groups', '1')!, app.store.getById<Group>('groups', '4')!],
    });

    expect(selector).toContainRaw('Plural 1');
    expect(selector).toContainRaw('Plural 4');

    selector.click('.Dropdown-item--4');
    expect(onchange).toHaveBeenLastCalledWith(['1', '4']);

    selector.click('.Dropdown-item--1');
    expect(onchange).toHaveBeenLastCalledWith(['4']);
  });
});
