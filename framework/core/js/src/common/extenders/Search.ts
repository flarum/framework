import type IExtender from './IExtender';
import type { IExtensionModule } from './IExtender';
import type Application from '../Application';
import IGambit from '../query/IGambit';

export default class Search implements IExtender {
  protected gambits: Record<string, Array<new () => IGambit>> = {};

  public gambit(modelType: string, gambit: new () => IGambit): this {
    this.gambits[modelType] = this.gambits[modelType] || [];
    this.gambits[modelType].push(gambit);

    return this;
  }

  extend(app: Application, extension: IExtensionModule): void {
    for (const [modelType, gambits] of Object.entries(this.gambits)) {
      for (const gambit of gambits) {
        app.store.gambits.gambits[modelType] = app.store.gambits.gambits[modelType] || [];
        app.store.gambits.gambits[modelType].push(gambit);
      }
    }
  }
}
