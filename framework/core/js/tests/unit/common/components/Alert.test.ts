import Alert from '../../../../src/common/components/Alert';
import m from 'mithril';
import mq from 'mithril-query';
import { jest } from '@jest/globals';

describe('Alert displays as expected', () => {
  it('should display alert messages with an icon', () => {
    const alert = mq(m(Alert, { type: 'error' }, 'Shoot!'));
    expect(alert).toContainRaw('Shoot!');
    expect(alert).toHaveElement('i.icon');
  });

  it('should display alert messages with a custom icon when using a title', () => {
    const alert = mq(Alert, { type: 'error', icon: 'fas fa-users', title: 'Woops..' });
    expect(alert).toContainRaw('Woops..');
    expect(alert).toHaveElement('i.fas.fa-users');
  });

  it('should display alert messages with a title', () => {
    const alert = mq(m(Alert, { type: 'error', title: 'Error Title' }, 'Shoot!'));
    expect(alert).toContainRaw('Shoot!');
    expect(alert).toContainRaw('Error Title');
    expect(alert).toHaveElement('.Alert-title');
  });

  it('should display alert messages with custom controls', () => {
    const alert = mq(Alert, { type: 'error', controls: [m('button', { className: 'Button--test' }, 'Click me!')] });
    expect(alert).toHaveElement('button.Button--test');
  });
});

describe('Alert is dismissible', () => {
  it('should show dismiss button', function () {
    const alert = mq(m(Alert, { dismissible: true }, 'Shoot!'));
    expect(alert).toHaveElement('button.Alert-dismiss');
  });

  it('should call ondismiss when dismiss button is clicked', function () {
    const ondismiss = jest.fn();
    const alert = mq(Alert, { dismissible: true, ondismiss });
    alert.click('.Alert-dismiss');
    expect(ondismiss).toHaveBeenCalled();
  });

  it('should not be dismissible if not chosen', function () {
    const alert = mq(Alert, { type: 'error', dismissible: false });
    expect(alert).not.toHaveElement('button.Alert-dismiss');
  });
});
