import app from 'flarum/forum/app';
import Group from 'flarum/common/models/Group';
import type IMentionableModel from './IMentionableModel';
import type Mithril from 'mithril';
import Badge from 'flarum/common/components/Badge';
import highlight from 'flarum/common/helpers/highlight';
import MentionTextGenerator from '../utils/MentionTextGenerator';

export default class GroupMention implements IMentionableModel<Group> {
  type(): string {
    return 'group';
  }

  initialResults(): Group[] {
    return Array.from(
      app.store.all<Group>('groups').filter((g: Group) => {
        return g.id() !== Group.GUEST_ID && g.id() !== Group.MEMBER_ID;
      })
    );
  }

  replacement(model: Group): string {
    return MentionTextGenerator.forGroup(model);
  }

  suggestion(model: Group, typed: string): Mithril.Children {
    let groupName: Mithril.Children = model.namePlural().toLowerCase();

    if (typed) {
      groupName = highlight(groupName, typed);
    }

    return (
      <>
        <Badge class={`Avatar Badge Badge--group--${model.id()} Badge-icon `} color={model.color()} type="group" icon={model.icon()} />
        <span className="username">{groupName}</span>
      </>
    );
  }

  matches(model: Group, typed: string): boolean {
    if (!typed) return false;

    const names = [model.namePlural().toLowerCase(), model.nameSingular().toLowerCase()];

    return names.some((name) => name.toLowerCase().substr(0, typed.length) === typed);
  }

  maxStoreMatchedResults(): null {
    return null;
  }

  /**
   * All groups are already loaded, so we don't need to search for them.
   */
  search(typed: string): Promise<Group[]> {
    return Promise.resolve([]);
  }

  enabled(): boolean {
    return app.session?.user?.canMentionGroups() ?? false;
  }
}
