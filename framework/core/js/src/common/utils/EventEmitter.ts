export default class EventEmitter {
  protected events: any = {};

  public constructor() {
    this.events = {};
  }

  public on(event: string, listener: Function): EventEmitter {
    if (!this.events[event]) {
      this.events[event] = [];
    }

    this.events[event].push(listener);

    return this;
  }

  public emit(event: string, ...args: any[]): void {
    if (this.events[event]) {
      this.events[event].forEach((listener: Function) => listener(...args));
    }
  }
}
