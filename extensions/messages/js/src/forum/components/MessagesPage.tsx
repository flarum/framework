import app from 'flarum/forum/app';
import Page, { IPageAttrs } from 'flarum/common/components/Page';
import PageStructure from 'flarum/forum/components/PageStructure';
import Mithril from 'mithril';
import Icon from 'flarum/common/components/Icon';
import DialogList from './DialogList';
import Dialog from '../../common/models/Dialog';
import LoadingIndicator from 'flarum/common/components/LoadingIndicator';
import Stream from 'flarum/common/utils/Stream';
import InfoTile from 'flarum/common/components/InfoTile';
import MessagesSidebar from './MessagesSidebar';
import DialogSection from './DialogSection';
import listItems from 'flarum/common/helpers/listItems';
import ItemList from 'flarum/common/utils/ItemList';
import Dropdown from 'flarum/common/components/Dropdown';
import Button from 'flarum/common/components/Button';

export interface IMessagesPageAttrs extends IPageAttrs {}

export default class MessagesPage<CustomAttrs extends IMessagesPageAttrs = IMessagesPageAttrs> extends Page<CustomAttrs> {
  protected selectedDialog = Stream<Dialog | null>(null);

  oninit(vnode: Mithril.Vnode<CustomAttrs, this>) {
    super.oninit(vnode);

    if (!app.session.user) {
      m.route.set(app.route('index'));
      return;
    }

    app.current.set('noTagsList', true);

    if (!app.dialogs.hasItems()) {
      app.dialogs.refresh().then(async () => {
        if (app.dialogs.hasItems()) {
          await this.initDialog();
        }
      });
    } else {
      this.initDialog();
    }
  }

  dialogRequestParams() {
    return {
      include: 'users.groups',
    };
  }

  protected async initDialog() {
    const dialogId = m.route.param('id');

    const title = app.translator.trans('flarum-messages.forum.messages_page.title', {}, true);

    let dialog: Dialog | null;

    if (dialogId) {
      dialog =
        app.store.getById<Dialog>('dialogs', dialogId) || ((await app.store.find<Dialog>('dialogs', dialogId, this.dialogRequestParams())) as Dialog);
    } else {
      dialog = app.dialogs.getAllItems()[0];
    }

    this.selectedDialog(dialog);

    if (dialog) {
      app.setTitle(dialog.title());
      app.history.push('dialog', dialog.title());
    } else {
      app.setTitle(title);
      app.history.push('messages', title);
    }

    m.redraw();
  }

  onupdate(vnode: Mithril.VnodeDOM<CustomAttrs, this>) {
    super.onupdate(vnode);

    // Scroll the dialog list to the active dialog item if present and not visible.
    const dialogElement = this.element.querySelector('.DialogListItem.active');
    const container = this.element.querySelector('.DialogList')!;

    if (dialogElement && $(container).offset()!.top + container.clientHeight <= $(dialogElement).offset()!.top) {
      dialogElement.scrollIntoView();
    }
  }

  view() {
    return (
      <PageStructure className="MessagesPage Page--vertical" loading={false} hero={this.hero.bind(this)} sidebar={() => <MessagesSidebar />}>
        {app.dialogs.isLoading() ? (
          <LoadingIndicator />
        ) : !app.dialogs.hasItems() ? (
          <InfoTile icon="far fa-envelope-open">{app.translator.trans('flarum-messages.forum.messages_page.empty_text')}</InfoTile>
        ) : (
          <div className="MessagesPage-content">
            <div className="MessagesPage-sidebar" key="sidebar">
              <div className="IndexPage-toolbar" key="toolbar">
                <ul className="IndexPage-toolbar-view">{listItems(this.viewItems().toArray())}</ul>
                <ul className="IndexPage-toolbar-action">{listItems(this.actionItems().toArray())}</ul>
              </div>
              <DialogList key="list" state={app.dialogs} activeDialog={this.selectedDialog()} />
            </div>
            {this.selectedDialog() ? (
              <DialogSection key="dialog" dialog={this.selectedDialog()} />
            ) : (
              <LoadingIndicator key="loading" display="block" />
            )}
          </div>
        )}
      </PageStructure>
    );
  }

  hero(): Mithril.Children {
    return (
      <header className="Hero MessagesPageHero">
        <div className="container">
          <div className="containerNarrow">
            <h1 className="Hero-title">
              <Icon name="fas fa-envelope" /> {app.translator.trans('flarum-messages.forum.messages_page.hero.title')}
            </h1>
            <div className="Hero-subtitle">{app.translator.trans('flarum-messages.forum.messages_page.hero.subtitle')}</div>
          </div>
        </div>
      </header>
    );
  }

  /**
   * Build an item list for the part of the toolbar which is concerned with how
   * the results are displayed. By default this is just a select box to change
   * the way discussions are sorted.
   */
  viewItems() {
    const items = new ItemList<Mithril.Children>();
    const sortMap = app.dialogs.sortMap();

    const sortOptions = Object.keys(sortMap).reduce((acc: any, sortId) => {
      const sort = sortMap[sortId];
      acc[sortId] = typeof sort !== 'string' ? sort.label : app.translator.trans(`flarum-messages.forum.index_sort.${sortId}_button`);
      return acc;
    }, {});

    items.add(
      'sort',
      <Dropdown
        buttonClassName="Button"
        label={sortOptions[app.dialogs.getParams()?.sort || 0] || Object.values(sortOptions)[0]}
        accessibleToggleLabel={app.translator.trans('core.forum.index_sort.toggle_dropdown_accessible_label')}
      >
        {Object.keys(sortOptions).map((value) => {
          const label = sortOptions[value];
          const active = (app.dialogs.getParams().sort || Object.keys(sortMap)[0]) === value;

          return (
            <Button icon={active ? 'fas fa-check' : true} onclick={() => app.dialogs.changeSort(value)} active={active}>
              {label}
            </Button>
          );
        })}
      </Dropdown>
    );

    return items;
  }

  /**
   * Build an item list for the part of the toolbar which is about taking action
   * on the results. By default this is just a "mark all as read" button.
   */
  actionItems() {
    const items = new ItemList<Mithril.Children>();

    items.add(
      'refresh',
      <Button
        title={app.translator.trans('flarum-messages.forum.messages_page.refresh_tooltip')}
        aria-label={app.translator.trans('flarum-messages.forum.messages_page.refresh_tooltip')}
        icon="fas fa-sync"
        className="Button Button--icon"
        onclick={() => {
          app.dialogs.refresh();
        }}
      />
    );

    if (app.session.user) {
      items.add(
        'markAllAsRead',
        <Button
          title={app.translator.trans('flarum-messages.forum.messages_page.mark_all_as_read_tooltip')}
          aria-label={app.translator.trans('flarum-messages.forum.messages_page.mark_all_as_read_tooltip')}
          icon="fas fa-check"
          className="Button Button--icon"
          onclick={() => app.dialogs.markAllAsRead()}
        />
      );
    }

    return items;
  }
}
