import app from '../app';
import FormModal, { type IFormModalAttrs } from './FormModal';
import type User from '../models/User';
import Mithril from 'mithril';
import Stream from '../utils/Stream';
import classList from '../utils/classList';
import Avatar from './Avatar';
import Button from './Button';
import LoadingIndicator from './LoadingIndicator';
import { throttle } from '../utils/throttleDebounce';
import InfoTile from './InfoTile';
import UserSearchResult from '../../forum/components/UserSearchResult';
import Pill from './Pill';

export interface IUserSelectionModalAttrs extends IFormModalAttrs {
  title?: string;
  selected: User[];
  onsubmit: (users: User[]) => void;
  maxItems?: number;
  excluded?: (number | string)[];
}

/**
 * The `UserSelectionModal` component displays a modal dialog with searchable
 * user list and submit button. The user can select one or more users from the
 * list and submit them to the callback.
 */
export default class UserSelectionModal<CustomAttrs extends IUserSelectionModalAttrs = IUserSelectionModalAttrs> extends FormModal<CustomAttrs> {
  protected search: Stream<string> = Stream('');
  protected selected!: Stream<User[]>;
  protected focused = false;
  protected results = Stream<Record<string, User[] | null>>({});

  oninit(vnode: Mithril.Vnode<CustomAttrs, this>) {
    super.oninit(vnode);

    this.selected = Stream(this.attrs.selected || []);

    // preload.
    this.load();
  }

  className(): string {
    return 'UserSelectionModal Modal--simple';
  }

  title(): Mithril.Children {
    return this.attrs.title || app.translator.trans('core.lib.user_selection_modal.title');
  }

  content(): Mithril.Children {
    let list = this.attrs.maxItems && this.selected().length === this.attrs.maxItems ? this.selected() : this.results()[this.search()] || [];

    if (this.attrs.excluded) {
      list = list.filter((user) => !this.attrs.excluded?.map(String).includes(user.id()!));
    }

    return [
      <div className="Modal-body">
        <div className="UserSelectionModal-form">
          <div className="UserSelectionModal-form-input">
            <input
              type="text"
              className="FormControl"
              placeholder={
                this.attrs.maxItems
                  ? app.translator.trans('core.lib.user_selection_modal.max_items_to_select', { count: this.attrs.maxItems }, true)
                  : app.translator.trans('core.lib.user_selection_modal.search_placeholder')
              }
              value={this.search()}
              oninput={(e: Event) => {
                this.search((e.target as HTMLInputElement).value);
                this.load();
              }}
              onfocus={() => (this.focused = true)}
              onblur={() => (this.focused = false)}
              disabled={this.attrs.maxItems && this.selected().length === this.attrs.maxItems}
            />
          </div>
          <div className="UserSelectionModal-form-submit App-primaryControl">
            <Button type="submit" className="Button Button--primary" disabled={!this.meetsRequirements()} icon="fas fa-check">
              {app.translator.trans('core.lib.user_selection_modal.submit_button')}
            </Button>
          </div>
        </div>
        <div className="UserSelectionModal-selected Pill-list">
          {this.selected().map((user) => (
            <Pill
              deletable
              ondelete={() => {
                const selected = this.selected().filter((u) => u !== user);
                this.selected(selected);

                if (!selected.length) {
                  this.load();
                }
              }}
              className="Pill-alt"
            >
              <Avatar user={user} />
              {user.displayName()}
            </Pill>
          ))}
        </div>
      </div>,

      this.loading || this.results()[this.search()] ? (
        <div className="Modal-footer">
          {this.loading ? (
            <LoadingIndicator />
          ) : this.results() && !this.results()[this.search()]?.length ? (
            <InfoTile icon="fas fa-search">{app.translator.trans('core.lib.user_selection_modal.empty_results')}</InfoTile>
          ) : (
            <ul className="UserSelectionModal-list Dropdown-menu">{list.map((user) => this.userListItem(user))}</ul>
          )}
        </div>
      ) : null,
    ];
  }

  userListItem(user: User) {
    const selected = this.selected().includes(user);

    return (
      <UserSearchResult
        user={user}
        query={this.search()}
        className={classList({
          'UserSelectionModal-listItem': true,
          'UserSelectionModal-listItem--selected': selected,
        })}
        onclick={() => {
          if (selected) {
            this.selected(this.selected().filter((u) => u !== user));
          } else {
            this.selected([...this.selected(), user]);
          }
        }}
      >
        <input type="checkbox" checked={selected} readOnly />
      </UserSearchResult>
    );
  }

  meetsRequirements(): boolean {
    return this.selected().length > 0 && this.selected().length <= (this.attrs.maxItems || Infinity);
  }

  onsubmit(e: SubmitEvent) {
    e.preventDefault();

    if (this.attrs.onsubmit) this.attrs.onsubmit(this.selected());

    this.hide();
  }

  protected load = throttle(500, () => {
    if (this.results()[this.search()]) return;

    if (this.attrs.maxItems && this.selected().length === this.attrs.maxItems) return;

    this.loading = true;

    return app.store
      .find<User[]>('users', { filter: { q: this.search() } })
      .then((results) => {
        this.results({ ...this.results(), [this.search()]: results });
      })
      .finally(() => {
        this.loading = false;
        m.redraw();
      });
  });
}
