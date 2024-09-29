import app from '../../common/app';
import ItemList from './ItemList';

export type LocalizedFormat = {
  /** Regexes that matches the formats. */
  regexes: string[];
  /** The map that turns matched localized formats to original DayJS formats. */
  formats: Record<string, string>
}

const defaultFormats = new ItemList<LocalizedFormat>();

defaultFormats.add("flarum-date-formats", {
  regexes: ["f{1,2}", "F{1,2}"],
  formats: {
    F: "DD MMMM",
    FF: "MMMM YYYY"
  }
}, 10);

defaultFormats.add("dayjs-formats", {
  regexes: ["LTS?", "l{1,4}", "L{1,4}"],
  formats: {
    LTS: 'h:mm:ss A',
    LT: 'h:mm A',
    L: 'MM/DD/YYYY',
    LL: 'MMMM D, YYYY',
    LLL: 'MMMM D, YYYY h:mm A',
    LLLL: 'dddd, MMMM D, YYYY h:mm A'
  }
}, 20);

const exports = {
  /**
   * Returns a list of regexes that matches all localized formats and a map that maps the format to the original DayJS format.
   */
  formatList: function() {
    const formats = new ItemList<LocalizedFormat>();
    formats.merge(defaultFormats);
    return formats;
  }
};

/**
 * Flarum's localized format plugin.
 * @see https://day.js.org/docs/en/plugin/plugin
 */
export const customFormats: import('dayjs').PluginFunc = function (_option, c, _factory) {
  const proto = c.prototype;
  const oldFormat = proto.format;

  /** Converts the long date to short date. */
  const t = (format?: string) => format?.replace(/(\[[^\]]+])|(MMMM|MM|DD|dddd)/g, (_, a, b) => a || b.slice(1));

  proto.format = function (template) {
    const { formats = {} } = (this as any).$locale();
    
    const config = exports.formatList().toArray();
    const regexes: string[] = [];
    let defFormats: Record<string, string> = {};
    config.forEach((v) => {
      regexes.push(...v.regexes);
      defFormats = { ...defFormats, ...v.formats };
    });
    
    const result = template?.replace(new RegExp(`(\\[[^\\]]+])|(${regexes.join("|")})`, "g"), (_, a, b) => {
      const B = b && b.toUpperCase();
      // The format is fetched in the following order: Translator > DayJS locale > formatList.
      console.log(regexes, defFormats);
      return a ||
        // short dates
        app.translator.translations[`core.forum.date-format.${b}`] ||
        formats[b] ||
        defFormats[b] ||
        // long dates
        t(app.translator.translations[`core.forum.date-format.${B}`]) ||
        t(formats[B]) ||
        t(defFormats[B]);
    });
    return oldFormat.call(this, result);
  };
};

export default exports;