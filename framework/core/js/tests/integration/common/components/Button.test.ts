import bootstrapForum from '@flarum/jest-config/src/boostrap/forum';
import Button from '../../../../src/common/components/Button';
import m from 'mithril';
import mq from 'mithril-query';
import { jest } from '@jest/globals';
import { app } from '../../../../src/forum';

beforeAll(() => bootstrapForum());

describe('Button displays as expected', () => {
  beforeAll(() => {
    app.boot();
  });

  it('renders button with text and icon', () => {
    const button = mq(
      m(
        Button,
        {
          icon: 'fas fa-check',
          'aria-label': 'Aria label',
        },
        'Submit'
      )
    );

    expect(button).toHaveElement('button.hasIcon');
    expect(button).toHaveElementAttr('button', 'aria-label', 'Aria label');
    expect(button).toHaveElement('.Button-label');
    expect(button).toContainRaw('Submit');
    expect(button).toHaveElement('.icon.fas.fa-check');
  });

  it('can be disabled', () => {
    const onclick = jest.fn();
    const button = mq(
      Button,
      {
        disabled: true,
        onclick,
      },
      'Submit'
    );

    expect(button).toHaveElement('button.disabled');
    button.click('button');
    expect(onclick).not.toHaveBeenCalled();
  });

  it('can be clicked', () => {
    const onclick = jest.fn();
    const button = mq(Button, { onclick });
    button.click('button');
    expect(onclick).toHaveBeenCalled();
  });

  it('can be loading', () => {
    const onclick = jest.fn();
    const button = mq(
      Button,
      {
        loading: true,
        onclick,
      },
      'Submit'
    );

    expect(button).toHaveElement('.loading');
    button.click('button');
    expect(onclick).not.toHaveBeenCalled();
  });
});
