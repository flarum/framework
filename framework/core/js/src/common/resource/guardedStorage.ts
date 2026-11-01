/**
 * JSON values in `localStorage`, guarded: reads and writes never throw. Every write is also kept in
 * memory, and the memory wins over storage, so a value whose write failed (private mode, quota, a
 * sandboxed frame) still reads back for the session.
 */
export default class GuardedStorage {
  protected memory = new Map<string, unknown>();

  /** The remembered or stored value; undefined when there is none or it is unreadable. */
  read(key: string): unknown {
    if (this.memory.has(key)) return JSON.parse(JSON.stringify(this.memory.get(key)));

    try {
      const raw = window.localStorage.getItem(key);

      return raw === null ? undefined : JSON.parse(raw);
    } catch {
      return undefined;
    }
  }

  write(key: string, value: unknown): void {
    this.memory.set(key, value);

    try {
      window.localStorage.setItem(key, JSON.stringify(value));
    } catch {
      // The in-memory value lasts for the session.
    }
  }

  /** Forget the in-memory values (storage is left alone). */
  clear(): void {
    this.memory.clear();
  }
}
