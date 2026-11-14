import bootstrapForum from '@flarum/jest-config/src/bootstrap/forum';
import app from '../../../../src/forum/app';
import LogInModal from '../../../../src/forum/components/LogInModal';
import RequestError from '../../../../src/common/utils/RequestError';
import Stream from '../../../../src/common/utils/Stream';

beforeAll(() => {
  bootstrapForum();
  app.boot();
});

function modal(): LogInModal {
  const modal = Object.create(LogInModal.prototype) as LogInModal;

  modal.password = Stream('incorrect');
  (modal as any).onready = () => {};

  return modal;
}

function requestError(status: number): RequestError {
  const error = new RequestError(status, '', { url: '/api/token' } as any, {} as any);
  error.alert = { type: 'error', content: 'The default message' };

  return error;
}

describe('LogInModal', () => {
  test('says the login details were wrong when they are refused', () => {
    const logIn = modal();
    const error = requestError(401);

    logIn.onerror(error);

    expect(error.alert!.content).toEqual(app.translator.trans('core.forum.log_in.invalid_login_message'));
    expect(logIn.password()).toEqual('');
  });

  test('says to wait when there have been too many failed attempts', () => {
    const logIn = modal();
    const error = requestError(429);

    logIn.onerror(error);

    expect(error.alert!.content).toEqual(app.translator.trans('core.forum.log_in.too_many_attempts_message'));
    expect(error.alert!.content).not.toEqual('The default message');
  });
});
