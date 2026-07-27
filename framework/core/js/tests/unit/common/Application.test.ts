import app from '../../../src/forum/app';
import RequestError from '../../../src/common/utils/RequestError';
import extractText from '../../../src/common/utils/extractText';
import { jest } from '@jest/globals';
import m from 'mithril';
import type Mithril from 'mithril';

const trans = (key: string) => extractText(app.translator.trans(key));

function makeError(status: number, responseText: string | null = null, url: string = '/api/test'): RequestError {
  return new RequestError(status, responseText, { url, method: 'GET' } as any, { status } as XMLHttpRequest);
}

function catchError(error: RequestError, customErrorHandler?: (e: RequestError) => void): Promise<void> {
  return (app as any).requestErrorCatch(error, customErrorHandler).catch(() => {});
}

function activeAlerts(): { text: string; attrs: Record<string, unknown> }[] {
  return Object.values(app.alerts.getActiveAlerts()).map((state) => ({
    text: extractText(state.children as Mithril.Children),
    attrs: state.attrs as Record<string, unknown>,
  }));
}

function setOnline(value: boolean): void {
  Object.defineProperty(window.navigator, 'onLine', { value, configurable: true });
}

function flushPromises(): Promise<void> {
  // A macrotask runs after all pending promise chains, however deep.
  return new Promise((resolve) => setTimeout(resolve, 0));
}

beforeEach(() => {
  app.alerts.clear();
  (app as any).requestErrorAlert = null;
  (app as any).networkErrorAlert = null;
  (app as any).offlineAlert = null;
  (app as any).deferredRequests = [];
  setOnline(true);
});

describe('Application#requestErrorCatch handles network-level failures', () => {
  it('shows a dedicated connection alert for a network-level failure (status 0)', async () => {
    await catchError(makeError(0));

    const alerts = activeAlerts();
    expect(alerts).toHaveLength(1);
    expect(alerts[0].text).toBe(trans('core.lib.error.network_message'));
  });

  it('shows an offline message when the browser reports being offline', async () => {
    setOnline(false);

    await catchError(makeError(0));

    const alerts = activeAlerts();
    expect(alerts).toHaveLength(1);
    expect(alerts[0].text).toBe(trans('core.lib.error.offline_message'));
  });

  it('still shows the cross-origin message for a failed cross-origin request', async () => {
    await catchError(makeError(0, null, 'https://other-origin.example.com/api/test'));

    const alerts = activeAlerts();
    expect(alerts).toHaveLength(1);
    expect(alerts[0].text).toBe(trans('core.lib.error.generic_cross_origin_message'));
  });

  it('prefers the offline message over the cross-origin one while offline', async () => {
    setOnline(false);

    await catchError(makeError(0, null, 'https://other-origin.example.com/api/test'));

    const alerts = activeAlerts();
    expect(alerts).toHaveLength(1);
    expect(alerts[0].text).toBe(trans('core.lib.error.offline_message'));
  });

  it('shows only one alert for multiple network-level failures', async () => {
    await catchError(makeError(0));
    await catchError(makeError(0));
    await catchError(makeError(0));

    expect(activeAlerts()).toHaveLength(1);
  });

  it('rejects with the original RequestError', async () => {
    const error = makeError(0);

    await expect((app as any).requestErrorCatch(error, undefined)).rejects.toBe(error);
  });

  it('respects a custom error handler instead of showing an alert', async () => {
    const customErrorHandler = jest.fn();
    const error = makeError(0);

    await catchError(error, customErrorHandler);

    expect(customErrorHandler).toHaveBeenCalledWith(error);
    expect(activeAlerts()).toHaveLength(0);
    expect(error.alert).not.toBeNull();
  });
});

describe('Application#requestErrorCatch keeps existing behavior for server responses', () => {
  it('shows the generic message for a 500 response', async () => {
    await catchError(makeError(500, 'Internal Server Error'));

    const alerts = activeAlerts();
    expect(alerts).toHaveLength(1);
    expect(alerts[0].text).toBe(trans('core.lib.error.generic_message'));
  });

  it('shows validation error details for a 422 response', async () => {
    const responseText = JSON.stringify({ errors: [{ detail: 'The title is required.' }] });

    await catchError(makeError(422, responseText));

    const alerts = activeAlerts();
    expect(alerts).toHaveLength(1);
    expect(alerts[0].text).toBe('The title is required.');
  });

  it('shows one alert per error for consecutive server errors', async () => {
    await catchError(makeError(500, 'Internal Server Error'));
    await catchError(makeError(500, 'Internal Server Error'));

    expect(activeAlerts()).toHaveLength(2);
  });
});

describe('Application reacts to browser connectivity events', () => {
  it('shows a dismissible offline alert when the browser goes offline', () => {
    window.dispatchEvent(new Event('offline'));

    const alerts = activeAlerts();
    expect(alerts).toHaveLength(1);
    expect(alerts[0].text).toBe(trans('core.lib.error.offline_message'));
    expect(alerts[0].attrs.type).toBe('error');
    expect(alerts[0].attrs.dismissible).toBe(true);
  });

  it('shows only one alert for repeated offline events', () => {
    window.dispatchEvent(new Event('offline'));
    window.dispatchEvent(new Event('offline'));

    expect(activeAlerts()).toHaveLength(1);
  });

  it('replaces a request failure alert with the offline alert when the browser goes offline', async () => {
    await catchError(makeError(0));
    window.dispatchEvent(new Event('offline'));

    const alerts = activeAlerts();
    expect(alerts).toHaveLength(1);
    expect(alerts[0].text).toBe(trans('core.lib.error.offline_message'));
  });

  it('dismisses the offline alert and confirms once the connection is restored', () => {
    jest.useFakeTimers();

    try {
      window.dispatchEvent(new Event('offline'));
      window.dispatchEvent(new Event('online'));

      const alerts = activeAlerts();
      expect(alerts).toHaveLength(1);
      expect(alerts[0].text).toBe(trans('core.lib.connection_restored_message'));

      jest.runAllTimers();

      expect(activeAlerts()).toHaveLength(0);
    } finally {
      jest.useRealTimers();
    }
  });

  it('dismisses a request failure alert once the connection is restored', async () => {
    await catchError(makeError(0));

    jest.useFakeTimers();

    try {
      window.dispatchEvent(new Event('online'));

      jest.runAllTimers();

      expect(activeAlerts()).toHaveLength(0);
    } finally {
      jest.useRealTimers();
    }
  });

  it('does not show a confirmation when the connection was never lost', () => {
    window.dispatchEvent(new Event('online'));

    expect(activeAlerts()).toHaveLength(0);
  });
});

describe('Application defers GET requests that fail while offline', () => {
  let requestSpy: ReturnType<typeof jest.spyOn>;

  beforeEach(() => {
    requestSpy = jest.spyOn(m, 'request');
  });

  afterEach(() => {
    requestSpy.mockRestore();
  });

  it('holds a failed GET request and resolves it once the connection is restored', async () => {
    setOnline(false);
    requestSpy.mockImplementationOnce(() => Promise.reject(makeError(0)));

    const resolved = jest.fn();
    const rejected = jest.fn();
    app.request({ url: '/api/test' }).then(resolved, rejected);

    await flushPromises();

    expect(resolved).not.toHaveBeenCalled();
    expect(rejected).not.toHaveBeenCalled();
    expect(activeAlerts().some((alert) => alert.text === trans('core.lib.error.offline_message'))).toBe(true);

    const response = { data: [] };
    requestSpy.mockImplementationOnce(() => Promise.resolve(response));
    setOnline(true);
    window.dispatchEvent(new Event('online'));

    await flushPromises();

    expect(resolved).toHaveBeenCalledWith(response);
    expect(rejected).not.toHaveBeenCalled();
  });

  it('rejects a non-GET request that fails while offline', async () => {
    setOnline(false);
    requestSpy.mockImplementationOnce(() => Promise.reject(makeError(0)));

    await expect(app.request({ url: '/api/test', method: 'POST' })).rejects.toBeInstanceOf(RequestError);
  });

  it('rejects a GET request that fails at the network level while the browser reports being online', async () => {
    requestSpy.mockImplementationOnce(() => Promise.reject(makeError(0)));

    await expect(app.request({ url: '/api/test' })).rejects.toBeInstanceOf(RequestError);
    expect(activeAlerts().some((alert) => alert.text === trans('core.lib.error.network_message'))).toBe(true);
  });

  it('re-defers a retried GET request that fails while still offline', async () => {
    setOnline(false);
    requestSpy.mockImplementationOnce(() => Promise.reject(makeError(0)));

    const resolved = jest.fn();
    const rejected = jest.fn();
    app.request({ url: '/api/test' }).then(resolved, rejected);

    await flushPromises();

    // The browser briefly reports being online, but the retry fails while
    // offline again: the request must stay deferred, not reject.
    requestSpy.mockImplementationOnce(() => {
      setOnline(false);
      return Promise.reject(makeError(0));
    });
    setOnline(true);
    window.dispatchEvent(new Event('online'));

    await flushPromises();

    expect(resolved).not.toHaveBeenCalled();
    expect(rejected).not.toHaveBeenCalled();

    const response = { data: [] };
    requestSpy.mockImplementationOnce(() => Promise.resolve(response));
    setOnline(true);
    window.dispatchEvent(new Event('online'));

    await flushPromises();

    expect(resolved).toHaveBeenCalledWith(response);
    expect(rejected).not.toHaveBeenCalled();
  });
});
