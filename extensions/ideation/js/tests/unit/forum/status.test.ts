import bootstrapForum from '@flarum/jest-config/src/bootstrap/forum';
import app from 'flarum/forum/app';
import type Discussion from 'flarum/common/models/Discussion';
import { jest } from '@jest/globals';
import extend from '../../../src/forum/extend';
import { toggleVote } from '../../../src/forum/status';

beforeAll(() => {
  bootstrapForum();
  app.boot();
  // The attributes this extension adds to discussions.
  extend.forEach((extender) => extender.extend(app, { name: 'flarum-ideation', exports: {} } as any));
  app.session.user = app.store.pushPayload<any>({ data: { type: 'users', id: '1', attributes: { username: 'voter' } } });
});

afterEach(() => {
  jest.restoreAllMocks();
});

function deferred() {
  let resolve!: (value: any) => void;
  let reject!: (error: any) => void;
  const promise = new Promise<any>((res, rej) => ((resolve = res), (reject = rej)));

  return { promise, resolve, reject };
}

const tick = () => new Promise((r) => setTimeout(r));

/** An idea at `votes` votes, and the saves it makes, answered by hand. */
function idea(id: string, votes: number, voted = false) {
  const discussion = app.store.pushPayload<Discussion>({
    data: {
      type: 'discussions',
      id,
      attributes: { title: 'Idea ' + id, ideationStatus: 'under_review', ideationVotes: votes, ideationVoted: voted },
    },
  } as any);

  const saves: { body: any; answer: ReturnType<typeof deferred> }[] = [];
  jest.spyOn(app, 'request').mockImplementation(((options: any) => {
    const answer = deferred();
    saves.push({ body: options.body, answer });
    return answer.promise;
  }) as any);

  /** The server's answer to a save of `voted`, with the count it then has. */
  const reply = (voted: boolean, votes: number) => ({
    data: { type: 'discussions', id, attributes: { ideationVoted: voted, ideationVotes: votes } },
  });

  return { discussion, saves, reply };
}

const shown = (d: Discussion) => [d.ideationVoted(), d.ideationVotes()];

describe('voting', () => {
  it('sends one change at a time, so a quick vote and unvote ends unvoted', async () => {
    const { discussion, saves, reply } = idea('401', 3);

    toggleVote(discussion);
    toggleVote(discussion);
    expect(shown(discussion)).toEqual([false, 3]);

    // Only the vote is on its way; the unvote waits for it.
    expect(saves.map((s) => s.body.data.attributes.ideationVoted)).toEqual([true]);

    saves[0].answer.resolve(reply(true, 4));
    await tick();

    // The vote's answer never shows over the later click.
    expect(shown(discussion)).toEqual([false, 3]);
    expect(saves.map((s) => s.body.data.attributes.ideationVoted)).toEqual([true, false]);

    saves[1].answer.resolve(reply(false, 3));
    await tick();

    expect(saves).toHaveLength(2);
    expect(shown(discussion)).toEqual([false, 3]);
  });

  it('folds clicks made while a change is on its way into one', async () => {
    const { discussion, saves, reply } = idea('402', 3);

    toggleVote(discussion);
    toggleVote(discussion);
    toggleVote(discussion);
    expect(shown(discussion)).toEqual([true, 4]);

    saves[0].answer.resolve(reply(true, 5));
    await tick();

    // The server already has what the last click asked for (someone else voted too).
    expect(saves).toHaveLength(1);
    expect(shown(discussion)).toEqual([true, 5]);
  });

  it('puts back what the server last said when a change fails, not an older picture', async () => {
    const { discussion, saves, reply } = idea('403', 3);

    toggleVote(discussion);
    toggleVote(discussion);
    toggleVote(discussion);

    saves[0].answer.resolve(reply(true, 4));
    await tick();
    expect(saves).toHaveLength(1);

    // Unvote again, and that save fails.
    toggleVote(discussion);
    expect(shown(discussion)).toEqual([false, 3]);
    saves[1].answer.reject(new Error('The idea shipped.'));
    await tick();

    expect(shown(discussion)).toEqual([true, 4]);
  });
});
