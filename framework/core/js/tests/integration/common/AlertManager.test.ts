import bootstrapForum from '@flarum/jest-config/src/boostrap/forum';
import { app } from '../../../src/forum';
import mq from 'mithril-query';
import AlertManager from '../../../src/common/components/AlertManager';

beforeAll(() => bootstrapForum());

describe('AlertManager', () => {
  beforeAll(() => app.boot());

  test('can show and dismiss an alert', () => {
    const manager = mq(AlertManager, { state: app.alerts });

    const id = app.alerts.show({ type: 'success' }, 'Hello, world!');

    manager.redraw();

    expect(manager).toContainRaw('Hello, world!');

    app.alerts.dismiss(id);

    manager.redraw();

    expect(manager).not.toContainRaw('Hello, world!');
  });

  test('can clear all alerts', () => {
    const manager = mq(AlertManager, { state: app.alerts });

    app.alerts.show({ type: 'success' }, 'Hello, world!');
    app.alerts.show({ type: 'error' }, 'Goodbye, world!');

    manager.redraw();

    expect(manager).toContainRaw('Hello, world!');
    expect(manager).toContainRaw('Goodbye, world!');

    app.alerts.clear();

    manager.redraw();

    expect(manager).not.toContainRaw('Hello, world!');
    expect(manager).not.toContainRaw('Goodbye, world!');
  });
});
