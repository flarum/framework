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

describe('GroupSelector', () => {
  it('offers every group in the store, minus the excluded ones', () => {
    const selector = mq(m(GroupSelector, { value: '', onchange: () => {}, exclude: [Group.GUEST_ID, Group.MEMBER_ID] }));

    expect(selector).toHaveElement('.GroupSelector[role="radiogroup"]');
    expect(selector).toContainRaw('Plural 1');
    expect(selector).toContainRaw('Plural 4');
    expect(selector).not.toContainRaw('Plural 2');
    expect(selector).not.toContainRaw('Plural 3');
  });

  it('marks the selected group and reports a single id', () => {
    const onchange = jest.fn();
    const selector = mq(m(GroupSelector, { value: '4', onchange, exclude: [Group.GUEST_ID, Group.MEMBER_ID] }));

    expect(selector).toHaveElement('.GroupSelector-group.active[aria-checked="true"]');

    selector.click('.GroupSelector-group');

    expect(onchange).toHaveBeenCalledWith('1');
  });

  it('toggles ids in and out of an array when multiple', () => {
    const onchange = jest.fn();
    const selector = mq(
      m(GroupSelector, {
        value: ['1'],
        onchange,
        multiple: true,
        groups: [app.store.getById<Group>('groups', '1')!, app.store.getById<Group>('groups', '4')!],
      })
    );

    expect(selector).toHaveElement('.GroupSelector[role="group"]');

    selector.click('.GroupSelector-group:nth-child(1)');
    selector.click('.GroupSelector-group:nth-child(2)');

    expect(onchange).toHaveBeenNthCalledWith(1, []);
    expect(onchange).toHaveBeenNthCalledWith(2, ['1', '4']);
  });
});
