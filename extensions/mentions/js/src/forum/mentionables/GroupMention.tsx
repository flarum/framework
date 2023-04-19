import app from 'flarum/forum/app';
import Group from 'flarum/common/models/Group';
import MentionableModel from './MentionableModel';
import type Mithril from 'mithril';
import Badge from 'flarum/common/components/Badge';
import highlight from 'flarum/common/helpers/highlight';
import type AtMentionFormat from './formats/AtMentionFormat';

export default class GroupMention extends MentionableModel<Group, AtMentionFormat> {
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

  /**
   * Generates the mention syntax for a group mention.
   *
   * @"Name Plural"#gGroupID
   *
   * @example <caption>Group mention</caption>
   * // '@"Mods"#g4'
   * forGroup(group) // Group display name is 'Mods', group ID is 4
   */
  public replacement(group: Group): string {
    return this.format.format(group.namePlural(), 'g', group.id());
  }

  suggestion(model: Group, typed: string): Mithril.Children {
    let groupName: Mithril.Children = model.namePlural();

    if (typed) {
      groupName = highlight(groupName, typed);
    }

    return (
      <>
        <Badge className={`Avatar Badge Badge--group--${model.id()} Badge-icon`} color={model.color()} type="group" icon={model.icon()} />
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
