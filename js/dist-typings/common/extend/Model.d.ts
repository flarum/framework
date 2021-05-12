export default class Model {
    constructor(type: any, model?: any);
    type: any;
    attributes: any[];
    hasOnes: any[];
    hasManys: any[];
    model: any;
    attribute(name: any): Model;
    hasOne(type: any): Model;
    hasMany(type: any): Model;
    extend(app: any, extension: any): void;
}
