import { extend } from 'flarum/common/extend';
import app from 'flarum/forum/app';
import IndexPage from 'flarum/forum/components/IndexPage';
import DiscussionListState from 'flarum/forum/states/DiscussionListState';
import Button from 'flarum/common/components/Button';
import ItemList from 'flarum/common/utils/ItemList';
import extractText from 'flarum/common/utils/extractText';
import type Mithril from 'mithril';
import RealtimeState from '../../RealtimeState';
import DiscussionListActivity from '../../states/DiscussionListActivity';

export default function (): void {
  // Bound once for the session, not per IndexPage: activity that arrives while
  // the reader is on a discussion has to be waiting when they come back.
  const activity = new DiscussionListActivity();

  RealtimeState.onUserChannelReady((channel) => activity.bind(channel));
  RealtimeState.onPublicChannelReady((channel) => activity.bind(channel));

  extend(DiscussionListState.prototype, 'clear', function (this: DiscussionListState) {
    if (this === app.discussions) activity.listReloaded();
  });

  // After core's oninit, which has already decided whether to reload the list.
  extend(IndexPage.prototype, 'oninit', function () {
    activity.showList(app.current.get('routeName') ?? null);
  });

  extend(IndexPage.prototype, 'onremove', function () {
    activity.hideList();
  });

  extend(IndexPage.prototype, 'contentItems', function (this: any, items: ItemList<Mithril.Children>) {
    const websocketUpdates = activity.updates;

    if (websocketUpdates.isEmpty()) return;

    const releaseUpdates = (): void => {
      activity.release();
      m.redraw();
    };

    const buttonLabel = (releaseTimeout: number): Mithril.Children =>
      websocketUpdates.autoRelease()
        ? app.translator.trans('flarum-realtime.forum.push.discussion-list-new-activity-with-auto-release', {
            count: websocketUpdates.length(),
            releaseTimeout,
          })
        : app.translator.trans('flarum-realtime.forum.push.discussion-list-new-activity', {
            count: websocketUpdates.length(),
          });

    // Started when the button appears, not on every render, so a redraw
    // doesn't wind the countdown back.
    if (!websocketUpdates.isTimerRunning()) websocketUpdates.startTimer();

    websocketUpdates.onTimer((second: number) => {
      if (second === 0) return releaseUpdates();
      this.$('.DiscussionList-update > .Button-label').text(extractText(buttonLabel(second)));
    });

    items.add(
      'realtimeNewActivity',
      Button.component(
        {
          className: 'Button DiscussionList-update',
          'aria-live': 'polite',
          'aria-atomic': 'true',
          onclick: releaseUpdates,
        },
        buttonLabel(websocketUpdates.getReleaseInterval())
      ),
      95
    );
  });

  extend(IndexPage.prototype, 'actionItems', (items) => {
    items.remove('refresh');
  });
}
