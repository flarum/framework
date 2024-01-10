import type Mithril from 'mithril';
import app from 'flarum/admin/app';
import Component, { ComponentAttrs } from 'flarum/common/Component';
import LoadingIndicator from 'flarum/common/components/LoadingIndicator';
import Button from 'flarum/common/components/Button';
import Tooltip from 'flarum/common/components/Tooltip';
import { Extension } from 'flarum/admin/AdminApplication';
import icon from 'flarum/common/helpers/icon';
import ItemList from 'flarum/common/utils/ItemList';
import extractText from 'flarum/common/utils/extractText';
import Link from 'flarum/common/components/Link';

import Label from './Label';
import TaskOutputModal from './TaskOutputModal';
import humanDuration from '../utils/humanDuration';
import Task, { TaskOperations } from '../models/Task';
import Pagination from './Pagination';

interface QueueTableColumn extends ComponentAttrs {
  label: string;
  content: (task: Task) => Mithril.Children;
}

export default class QueueSection extends Component<{}> {
  oninit(vnode: Mithril.Vnode<{}, this>) {
    super.oninit(vnode);

    app.extensionManager.queue.load();
  }

  view() {
    return (
      <section id="ExtensionManager-queueSection" className="ExtensionPage-permissions ExtensionManager-queueSection">
        <div className="ExtensionPage-permissions-header ExtensionManager-queueSection-header">
          <div className="container">
            <h2 className="ExtensionTitle">{app.translator.trans('flarum-extension-manager.admin.sections.queue.title')}</h2>
            <Button
              className="Button Button--icon"
              icon="fas fa-sync-alt"
              onclick={() => app.extensionManager.queue.load()}
              aria-label={app.translator.trans('flarum-extension-manager.admin.sections.queue.refresh')}
              disabled={app.extensionManager.control.isLoading()}
            />
          </div>
        </div>
        <div className="container">{this.queueTable()}</div>
      </section>
    );
  }

  columns() {
    const items = new ItemList<QueueTableColumn>();

    items.add(
      'operation',
      {
        label: extractText(app.translator.trans('flarum-extension-manager.admin.sections.queue.columns.operation')),
        content: (task) => (
          <div className="ExtensionManager-queueTable-operation">
            <span className="ExtensionManager-queueTable-operation-icon">{this.operationIcon(task.operation())}</span>
            <span className="ExtensionManager-queueTable-operation-name">
              {app.translator.trans(`flarum-extension-manager.admin.sections.queue.operations.${task.operation()}`)}
            </span>
          </div>
        ),
      },
      80
    );

    items.add(
      'package',
      {
        label: extractText(app.translator.trans('flarum-extension-manager.admin.sections.queue.columns.package')),
        content: (task) => {
          const extension: Extension | null = app.data.extensions[task.package()?.replace(/(\/flarum-|\/flarum-ext-|\/)/g, '-')];

          return extension ? (
            <Link className="ExtensionManager-queueTable-package" href={app.route('extension', { id: extension.id })}>
              <div className="ExtensionManager-queueTable-package-icon ExtensionIcon" style={extension.icon}>
                {!!extension.icon && icon(extension.icon.name)}
              </div>
              <div className="ExtensionManager-queueTable-package-details">
                <span className="ExtensionManager-queueTable-package-title">{extension.extra['flarum-extension'].title}</span>
                <span className="ExtensionManager-queueTable-package-name">{task.package()}</span>
              </div>
            </Link>
          ) : (
            task.package()
          );
        },
      },
      75
    );

    items.add(
      'status',
      {
        label: extractText(app.translator.trans('flarum-extension-manager.admin.sections.queue.columns.status')),
        content: (task) => (
          <>
            <Label
              className="ExtensionManager-queueTable-status"
              type={{ running: 'neutral', failure: 'error', pending: 'warning', success: 'success' }[task.status()]}
            >
              {app.translator.trans(`flarum-extension-manager.admin.sections.queue.statuses.${task.status()}`)}
            </Label>
            {['pending', 'running'].includes(task.status()) && <LoadingIndicator size="small" display="inline" />}
          </>
        ),
      },
      70
    );

    items.add(
      'elapsedTime',
      {
        label: extractText(app.translator.trans('flarum-extension-manager.admin.sections.queue.columns.elapsed_time')),
        content: (task) =>
          !task.startedAt() || !task.finishedAt() ? (
            app.translator.trans('flarum-extension-manager.admin.sections.queue.task_just_started')
          ) : (
            <Tooltip text={`${dayjs(task.startedAt()).format('LL LTS')}  ${dayjs(task.finishedAt()).format('LL LTS')}`}>
              <span>{humanDuration(task.startedAt(), task.finishedAt())}</span>
            </Tooltip>
          ),
      },
      65
    );

    items.add(
      'memoryUsed',
      {
        label: extractText(app.translator.trans('flarum-extension-manager.admin.sections.queue.columns.peak_memory_used')),
        content: (task) => <span>{task.peakMemoryUsed()}</span>,
      },
      60
    );

    items.add(
      'details',
      {
        label: extractText(app.translator.trans('flarum-extension-manager.admin.sections.queue.columns.details')),
        content: (task) => (
          <Button
            className="Button Button--icon Table-controls-item"
            icon="fas fa-file-alt"
            aria-label={app.translator.trans('flarum-extension-manager.admin.sections.queue.columns.details')}
            // @todo fix in core
            // @ts-ignore
            onclick={() => app.modal.show(TaskOutputModal, { task })}
            disabled={['pending', 'running'].includes(task.status())}
          />
        ),
        className: 'Table-controls',
      },
      55
    );

    return items;
  }

  queueTable() {
    const tasks = app.extensionManager.queue.getItems();

    if (!tasks) {
      return <LoadingIndicator />;
    }

    if (tasks && !tasks.length) {
      return <h3 className="ExtensionPage-subHeader">{app.translator.trans('flarum-extension-manager.admin.sections.queue.none')}</h3>;
    }

    const columns = this.columns();

    return (
      <>
        <table className="Table ExtensionManager-queueTable">
          <thead>
            <tr>
              {columns.toArray().map((item, index) => (
                <th key={index}>{item.label}</th>
              ))}
            </tr>
          </thead>
          <tbody>
            {tasks.map((task, index) => (
              <tr key={index}>
                {columns.toArray().map((item, index) => {
                  const { label, content, ...attrs } = item;

                  return (
                    <td key={index} {...attrs}>
                      {content(task)}
                    </td>
                  );
                })}
              </tr>
            ))}
          </tbody>
        </table>

        <Pagination list={app.extensionManager.queue} />
      </>
    );
  }

  operationIcon(operation: TaskOperations): Mithril.Children {
    return icon(
      {
        update_check: 'fas fa-sync-alt',
        update_major: 'fas fa-play',
        update_minor: 'fas fa-play',
        update_global: 'fas fa-play',
        extension_install: 'fas fa-download',
        extension_remove: 'fas fa-times',
        extension_update: 'fas fa-arrow-alt-circle-up',
        why_not: 'fas fa-exclamation-circle',
      }[operation]
    );
  }
}
