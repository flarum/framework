import app from 'flarum/forum/app';
import Component, { type ComponentAttrs } from 'flarum/common/Component';
import type Mithril from 'mithril';
import Icon from 'flarum/common/components/Icon';
import classList from 'flarum/common/utils/classList';
import type TypingState from '../states/TypingState';

export interface TypingIndicatorAttrs extends ComponentAttrs {
  state: TypingState;
}

/**
 * Renders the "X, Y and Z are typing" indicator for a discussion.
 *
 * The component is purely presentational: it is given a {@link TypingState}
 * (which owns the currently-typing users, fed from the realtime socket) and
 * renders its active set. Because it holds no socket logic, it can be placed
 * anywhere — added to the PostStream `endItems` list by default, but also
 * importable and rendered standalone wherever a theme or extension keeps a
 * TypingState.
 */
export default class TypingIndicator extends Component<TypingIndicatorAttrs> {
  view(): Mithril.Children {
    const typingUsers = Object.keys(this.attrs.state.active());
    const count = typingUsers.length;
    const max = 3;

    const classes = classList(['TypingUsersContainer', count > 0 && 'TypingUsersContainer-active']);
    const typingIcon = count > 0 ? 'fas fa-ellipsis-h fa-beat' : 'fas fa-pause';
    const namedUsers = typingUsers.slice(0, max).join(', ');

    let showUsers = true;

    if (app.session?.user) {
      showUsers = app.session.user.preferences()?.['flarum-realtime.typing-indicator-full'] ?? true;
    }

    return (
      <div className={classes}>
        <div className="TypingUsers">
          <Icon name={typingIcon} />
          {count > 0
            ? showUsers
              ? app.translator.trans('flarum-realtime.forum.typing-indicator.users-are-typing', {
                  users: namedUsers,
                  count,
                  others: Math.max(count - max, 0),
                })
              : app.translator.trans('flarum-realtime.forum.typing-indicator.people-are-typing', { number: count })
            : app.translator.trans('flarum-realtime.forum.typing-indicator.no-activity')}
        </div>
      </div>
    );
  }
}
