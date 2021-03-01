export default class SearchState {
  constructor(cachedSearches = []) {
    this.cachedSearches = cachedSearches;
  }

  getValue() {
    return this.value;
  }

  setValue(value) {
    this.value = value;
  }

  /**
   * Clear the search value.
   */
  clear() {
    this.setValue('');
  }

  /**
   * Mark that we have already searched for this query so that we don't
   * have to ping the endpoint again.
   */
  cache(query) {
    this.cachedSearches.push(query);
  }

  /**
   * Check if this query has been searched before.
   */
  isCached(query) {
    return this.cachedSearches.indexOf(query) !== -1;
  }
}
