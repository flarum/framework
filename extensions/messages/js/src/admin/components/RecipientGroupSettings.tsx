import app from 'flarum/admin/app';
import Component, { type ComponentAttrs } from 'flarum/common/Component';
import Group from 'flarum/common/models/Group';
import GroupBadge from 'flarum/common/components/GroupBadge';

interface IRecipientGroupSettingsAttrs extends ComponentAttrs {
  value: string;
  onchange: (value: string) => void;
}

export default class RecipientGroupSettings extends Component<IRecipientGroupSettingsAttrs> {
  view() {
    const parsed: unknown = JSON.parse(this.attrs.value || '[]');
    const selected: number[] = Array.isArray(parsed) ? parsed.map(Number) : [];
    const groups = app.store.all<Group>('groups').filter((group) => ![Group.GUEST_ID, Group.MEMBER_ID].includes(group.id()!));
    return (
      <div className="Form-group RecipientGroupSettings">
        <label>{app.translator.trans('flarum-messages.admin.settings.recipient_groups_label')}</label>
        <p className="helpText">{app.translator.trans('flarum-messages.admin.settings.recipient_groups_help')}</p>
        {groups.map((group) => (
          <label className="checkbox" key={group.id()}>
            <input
              type="checkbox"
              data-group={group.id()}
              checked={selected.includes(Number(group.id()))}
              onchange={() => {
                const id = Number(group.id());
                this.attrs.onchange(JSON.stringify(selected.includes(id) ? selected.filter((value) => value !== id) : [...selected, id]));
              }}
            />
            <GroupBadge group={group} /> {group.namePlural()}
          </label>
        ))}
      </div>
    );
  }
}
