export interface HistoryEntry {
  name: string;
  title: string;
  url: string;
}

export default interface IHistory {
  canGoBack(): boolean;
  getCurrent(): HistoryEntry | null;
  getPrevious(): HistoryEntry | null;
  push(name: string, title: string, url: string): void;
  back(): void;
  backUrl(): string;
  home(): void;
}
