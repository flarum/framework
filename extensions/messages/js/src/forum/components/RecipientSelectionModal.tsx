import app from 'flarum/forum/app';
import UserSelectionModal, { type IUserSelectionModalAttrs } from 'flarum/common/components/UserSelectionModal';
import Group from 'flarum/common/models/Group';
import GroupBadge from 'flarum/common/components/GroupBadge';
import Stream from 'flarum/common/utils/Stream';
import type Mithril from 'mithril';

export interface IRecipientSelectionModalAttrs extends IUserSelectionModalAttrs {
  selectedGroups?: Group[];
  onsubmitGroups: (groups: Group[]) => void;
}

export default class RecipientSelectionModal extends UserSelectionModal<IRecipientSelectionModalAttrs> {
  protected selectedGroups = Stream<Group[]>([]);

  oninit(vnode: Mithril.Vnode<IRecipientSelectionModalAttrs, this>) {
    super.oninit(vnode);
    this.selectedGroups(this.attrs.selectedGroups || []);
    app.store.find<Group[]>('groups').then(() => m.redraw(), this.onerror.bind(this));
  }

  content(): Mithril.Children {
    const groups = [
      ...new Set([...this.selectedGroups(), ...app.store.all<Group>('groups').filter((group) => group.attribute<boolean>('canMessage') === true)]),
    ];
    return [
      super.content(),
      groups.length > 0 && (
        <div className="Modal-body RecipientSelectionModal-groups">
          <h3>{app.translator.trans('flarum-messages.forum.recipient_selection_modal.groups_heading')}</h3>
          <p className="helpText">{app.translator.trans('flarum-messages.forum.recipient_selection_modal.groups_help')}</p>
          {groups
            .filter((group) => group.namePlural().toLocaleLowerCase().includes(this.search().toLocaleLowerCase()))
            .map((group) => (
              <label className="RecipientSelectionModal-group">
                <input
                  type="checkbox"
                  data-group={group.id()}
                  checked={this.selectedGroups().includes(group)}
                  disabled={
                    !this.selectedGroups().includes(group) && (group.attribute<boolean>('canMessage') !== true || this.selectedGroups().length >= 10)
                  }
                  onchange={() =>
                    this.selectedGroups(
                      this.selectedGroups().includes(group)
                        ? this.selectedGroups().filter((selected) => selected !== group)
                        : [...this.selectedGroups(), group]
                    )
                  }
                />
                <GroupBadge group={group} /> {group.namePlural()}
              </label>
            ))}
        </div>
      ),
    ];
  }

  meetsRequirements(): boolean {
    return (
      this.selectedGroups().every((group) => group.attribute<boolean>('canMessage') === true) &&
      this.selectedGroups().length <= 10 &&
      (super.meetsRequirements() || (this.selected().length === 0 && this.selectedGroups().length > 0))
    );
  }

  onsubmit(e: SubmitEvent): void {
    if (!this.meetsRequirements()) return;
    this.attrs.onsubmitGroups(this.selectedGroups());
    super.onsubmit(e);
  }
}
