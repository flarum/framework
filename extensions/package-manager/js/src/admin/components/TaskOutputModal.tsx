import app from 'flarum/admin/app';
import Modal, { IInternalModalAttrs } from 'flarum/common/components/Modal';
import Task from '../models/Task';

interface TaskOutputModalAttrs extends IInternalModalAttrs {
  task: Task;
}

export default class TaskOutputModal<CustomAttrs extends TaskOutputModalAttrs = TaskOutputModalAttrs> extends Modal<CustomAttrs> {
  className() {
    return 'Modal--large QuickModal';
  }

  title() {
    return app.translator.trans(`flarum-extension-manager.admin.sections.queue.operations.${this.attrs.task.operation()}`);
  }

  content() {
    return (
      <div className="Modal-body">
        <div className="TaskOutputModal-data">
          {this.attrs.task.status() === 'failure' && (
            <div className="Form-group">
              <label>{app.translator.trans('flarum-extension-manager.admin.sections.queue.output_modal.guessed_cause')}</label>
              <div className="FormControl TaskOutputModal-data-guessed-cause">
                {(this.attrs.task.guessedCause() &&
                  app.translator.trans('flarum-extension-manager.admin.exceptions.guessed_cause.' + this.attrs.task.guessedCause())) ||
                  app.translator.trans('flarum-extension-manager.admin.sections.queue.output_modal.cause_unknown')}
              </div>
            </div>
          )}

          <div className="Form-group">
            <label>{app.translator.trans('flarum-extension-manager.admin.sections.queue.output_modal.command')}</label>
            <div className="FormControl TaskOutputModal-data-command">
              <code>$ composer {this.attrs.task.command()}</code>
            </div>
          </div>

          <div className="Form-group">
            <label>{app.translator.trans('flarum-extension-manager.admin.sections.queue.output_modal.output')}</label>
            <div className="FormControl TaskOutputModal-data-output">
              <code>
                <pre>{this.attrs.task.output()}</pre>
              </code>
            </div>
          </div>
        </div>
      </div>
    );
  }
}
