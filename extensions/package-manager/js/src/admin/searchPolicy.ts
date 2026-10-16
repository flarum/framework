/**
 * When a keystroke should become a search.
 *
 * 🚨 Live search is a request per keystroke unless something says otherwise,
 * and the something has to be a rule, not a feeling. This one is aimed at
 * somebody else's Packagist quota and somebody else's shared host: a person
 * typing "discussion" is eleven searches if nothing stops it, and ten of them
 * are for a prefix nobody wanted results for.
 *
 * Kept out of the component because a timing rule buried in an `oninput`
 * handler is a rule nobody can test, and this one has three edge cases that all
 * look reasonable and are all wrong.
 */

/** Long enough that a typist does not outrun it, short enough to feel live. */
export const SEARCH_DEBOUNCE_MS = 350;

/**
 * 🚨 Two, not one. A single letter matches most of Packagist, so it costs a
 * full round trip to return something nobody can use — and it is the character
 * every search passes through on the way to a real query.
 */
export const MIN_QUERY_LENGTH = 2;

export interface SearchPlan {
  search: boolean;
  delayMs: number;
}

const NOT_YET: SearchPlan = { search: false, delayMs: 0 };

/**
 * @param query        what is in the box now
 * @param lastSearched the query whose results are on screen, or null if none
 */
export function liveSearchPlan(query: string, lastSearched: string | null): SearchPlan {
  const trimmed = query.trim();

  /*
   * 🚨 Already showing it. Without this, blurring and refocusing, or typing a
   * character and deleting it, re-runs a search whose results are already on
   * screen — and because results arrive out of order under a stale-response
   * guard, the visible effect is a list that flickers for no reason.
   */
  if (lastSearched !== null && trimmed === lastSearched) {
    return NOT_YET;
  }

  /*
   * 🚨 An emptied box is a real intention, not an absence of one: it means
   * "show me what there is" — which is what this tab opens with. Treating it as
   * "too short" would leave the last search's results stranded under an empty
   * field.
   */
  if (trimmed.length === 0) {
    return { search: true, delayMs: SEARCH_DEBOUNCE_MS };
  }

  if (trimmed.length < MIN_QUERY_LENGTH) {
    return NOT_YET;
  }

  return { search: true, delayMs: SEARCH_DEBOUNCE_MS };
}

/**
 * Pressing the button or hitting Return means now, whatever the rule says.
 *
 * 🚨 An explicit action must never be refused by a heuristic meant for
 * keystrokes. Somebody searching a one-letter vendor prefix has asked for it,
 * and a control that silently does nothing is worse than no control.
 */
export function submittedSearchPlan(): SearchPlan {
  return { search: true, delayMs: 0 };
}
