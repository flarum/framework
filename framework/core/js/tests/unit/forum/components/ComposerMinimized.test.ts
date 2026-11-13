import bootstrapForum from '@flarum/jest-config/src/bootstrap/forum';
import app from '../../../../src/forum/app';
import m from 'mithril';
import mq from 'mithril-query';
import { jest } from '@jest/globals';
import Composer from '../../../../src/forum/components/Composer';
import ComposerState from '../../../../src/forum/states/ComposerState';

beforeAll(() => {
  bootstrapForum();
  app.boot();
});

afterEach(() => jest.restoreAllMocks());

// A composer at this position, with a body that renders nothing of its own.
const render = (position: string) => {
  const state = new ComposerState();
  state.position = position;
  state.body = { componentClass: { view: () => m('div') }, attrs: {} };
  jest.spyOn(state, 'show').mockResolvedValue(undefined);

  const composer = Object.create(Composer.prototype);
  composer.attrs = { state };
  composer.state = state;

  return { state, out: mq({ view: () => composer.view() }) };
};

describe('a minimized composer', () => {
  // Without it, the only way back from the keyboard would be to close it.
  test('has a control that restores it', () => {
    const { state, out } = render(ComposerState.Position.MINIMIZED);

    out.should.have('.Composer-controls .item-restore button[aria-label]');
    out.click('.Composer-controls .item-restore button');

    expect(state.show).toHaveBeenCalled();
  });

  test('is restored by a click anywhere on it', () => {
    const { state, out } = render(ComposerState.Position.MINIMIZED);

    out.click('.Composer-content');

    expect(state.show).toHaveBeenCalled();
  });

  // Anything in its body, such as a link to the discussion, mustn't sit inside a button.
  test('is not itself a button', () => {
    const { out } = render(ComposerState.Position.MINIMIZED);

    out.should.not.have('.Composer-content[role]');
  });
});

describe('an open composer', () => {
  test('has no restore control', () => {
    render(ComposerState.Position.NORMAL).out.should.not.have('.Composer-controls .item-restore');
  });
});
