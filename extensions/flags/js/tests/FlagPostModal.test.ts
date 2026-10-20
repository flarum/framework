import { jest } from '@jest/globals';
import m from 'mithril';
import app from 'flarum/forum/app';
import FlagPostModal from '../src/forum/components/FlagPostModal';
import FlagUserModal from '../src/forum/components/FlagUserModal';
import { bootFlags, resources, settle } from './fixtures';

beforeEach(bootFlags);
afterEach(() => jest.restoreAllMocks());

function modal(attrs: Record<string, any>, Modal: any = FlagUserModal) {
  const instance = new Modal();
  instance.oninit({ attrs } as any);
  instance.reason('spam');
  instance.reasonDetail('Repeated unsolicited messages');
  return instance;
}

describe('report submission target', () => {
  it('saves an account report with the targetUser relationship and no post relationship', async () => {
    const { targetUser } = resources();
    const save = jest.fn<any>().mockResolvedValue({});
    const createRecord = jest.spyOn(app.store, 'createRecord').mockReturnValue({ save } as any);
    jest.spyOn(m, 'redraw').mockImplementation(() => {});
    const instance = modal({ user: targetUser });
    const preventDefault = jest.fn();

    instance.onsubmit({ preventDefault });
    await settle();

    expect(preventDefault).toHaveBeenCalled();
    expect(createRecord).toHaveBeenCalledWith('flags');
    expect(save).toHaveBeenCalledWith(
      {
        reason: 'spam',
        reasonDetail: 'Repeated unsolicited messages',
        relationships: { targetUser },
      },
      expect.objectContaining({ errorHandler: expect.any(Function) })
    );
    expect(instance.success).toBe(true);
    expect(instance.loading).toBe(false);
  });

  it('preserves the existing post report relationship', async () => {
    const { post } = resources();
    const save = jest.fn<any>().mockResolvedValue({});
    jest.spyOn(app.store, 'createRecord').mockReturnValue({ save } as any);
    jest.spyOn(m, 'redraw').mockImplementation(() => {});
    const instance = modal({ post }, FlagPostModal);

    instance.onsubmit({ preventDefault() {} });
    await settle();

    expect(save.mock.calls[0][0].relationships).toEqual({ post });
    expect(instance.success).toBe(true);
  });

  it('retains the user-provided detail for Other and does not turn a failed save into success', async () => {
    const { targetUser } = resources();
    const save = jest.fn<any>().mockRejectedValue(new Error('Request failed'));
    jest.spyOn(app.store, 'createRecord').mockReturnValue({ save } as any);
    jest.spyOn(m, 'redraw').mockImplementation(() => {});
    const instance = modal({ user: targetUser });
    instance.reason('other');

    instance.onsubmit({ preventDefault() {} });
    await settle();

    expect(save.mock.calls[0][0]).toEqual({
      reason: null,
      reasonDetail: 'Repeated unsolicited messages',
      relationships: { targetUser },
    });
    expect(instance.success).toBe(false);
    expect(instance.loading).toBe(false);
  });

  it('does not submit an account report without a selected reason or while a save is already pending', async () => {
    const { targetUser } = resources();
    let finishSave: (value: any) => void;
    const save = jest.fn<any>().mockImplementation(() => new Promise((resolve) => (finishSave = resolve)));
    const createRecord = jest.spyOn(app.store, 'createRecord').mockReturnValue({ save } as any);
    jest.spyOn(m, 'redraw').mockImplementation(() => {});
    const instance = modal({ user: targetUser });
    instance.reason('');

    instance.onsubmit({ preventDefault() {} });
    expect(createRecord).not.toHaveBeenCalled();

    instance.reason('spam');
    instance.onsubmit({ preventDefault() {} });
    instance.onsubmit({ preventDefault() {} });
    expect(save).toHaveBeenCalledTimes(1);
    finishSave!({});
    await settle();
    instance.onsubmit({ preventDefault() {} });
    expect(save).toHaveBeenCalledTimes(1);
  });
});
