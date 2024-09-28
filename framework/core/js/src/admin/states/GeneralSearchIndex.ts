export type GeneralIndexItem = {
  id: string;
  tree?: string[];
  label: string;
  help?: string;
  link?: string;
  visible?: () => boolean;
};

export type GeneralIndexData = Record<string, Record<'settings' | 'permissions', GeneralIndexItem[]>>;

export type GeneralIndexGroup = {
  label: string;
  icon: {
    name: string;
    [key: string]: any;
  };
  link: string;
};

export default class GeneralSearchIndex {
  protected currentId: string = '';
  protected data: GeneralIndexData = {};
  protected groups: Record<string, GeneralIndexGroup> = {};

  public group(id: string, data: GeneralIndexGroup) {
    this.groups[id] = data;

    return this;
  }

  public for(id: string) {
    this.currentId = id;

    return this;
  }

  public add(type: 'settings' | 'permissions', items: GeneralIndexItem[]) {
    this.data[this.currentId] ||= {
      settings: [],
      permissions: [],
    };
    this.data[this.currentId][type].push(...items);
  }

  public getData() {
    return this.data;
  }

  public getGroup(id: string): null | GeneralIndexGroup {
    return this.groups[id] || null;
  }
}
