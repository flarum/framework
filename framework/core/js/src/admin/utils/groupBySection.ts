export interface SectionGroup<T> {
  /** The section id, or `null` for entries shown before any section. */
  section: string | null;
  entries: T[];
}

/**
 * Splits an ordered list of entries into sections.
 *
 * An entry without a known section joins the section of the entry before it,
 * so something added between two entries of a section (by priority) stays
 * where it was shown before sections existed. Entries before the first
 * sectioned one form a leading group with a `null` section.
 *
 * @param entries The entries, in display order.
 * @param sectionOf The section an entry declares, if any.
 * @param sections The known section ids, in display order.
 */
export default function groupBySection<T>(entries: T[], sectionOf: (entry: T) => string | null | undefined, sections: string[]): SectionGroup<T>[] {
  const known = new Set(sections);
  const members = new Map<string | null, T[]>();
  let current: string | null = null;

  entries.forEach((entry) => {
    const declared = sectionOf(entry);

    if (declared && known.has(declared)) current = declared;

    if (!members.has(current)) members.set(current, []);
    members.get(current)!.push(entry);
  });

  return [null, ...sections].filter((id) => members.has(id)).map((id) => ({ section: id, entries: members.get(id)! }));
}
