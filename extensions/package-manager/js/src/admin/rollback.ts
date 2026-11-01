import app from 'flarum/admin/app';
import apiUrl from './apiUrl';
import { alertError } from './errors';
import type { RollbackPayload, Run } from './types';
import { confirmAction } from './components/ConfirmModal';

/**
 * Asks before undoing a run, and says whether the database goes back too.
 *
 * Every way to undo a run comes through here: a finished run, a failed one,
 * and the History tab. Undoing a FAILED run skipped it at first, so the one
 * case where migrations may have half run was the one with no warning that
 * they would be reversed.
 */
export function confirmRollback(run: Run, onconfirm: () => void): void {
  const count = run.migrations?.length || 0;

  confirmAction({
    title: app.translator.trans('flarum-extension-manager.admin.run.roll_back'),
    message: count
      ? app.translator.trans('flarum-extension-manager.admin.run.roll_back_confirm_db', { count })
      : app.translator.trans('flarum-extension-manager.admin.run.roll_back_confirm'),
    confirmText: app.translator.trans('flarum-extension-manager.admin.run.roll_back'),
    danger: true,
    onconfirm,
  });
}

/** Undoes the latest run, the only one the endpoint acts on. A refusal is shown as the server's words. */
export function requestRollback(): Promise<RollbackPayload> {
  return app.request<RollbackPayload>({
    method: 'POST',
    url: apiUrl() + '/extension-manager/rollback',
    errorHandler: alertError('flarum-extension-manager.admin.run.rollback_failed'),
  });
}
