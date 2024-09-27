export const customFormats: import('dayjs').PluginFunc = function (_option, c, _factory) {
    const proto = c.prototype;
    const oldFormat = proto.format;

    const t = (format?: string) =>
        format?.replace(/(\[[^\]]+])|(MMMM|MM|DD|dddd)/g, (_, a, b) => a || b.slice(1))

    const englishFormats: Record<string, string> = {
        F: 'DD MMMM',
        FF: 'MMMM YYYY'
    };

    proto.format = function(template) {
        const { formats = {} } = (this as any).$locale();
        const result = template?.replace(/(\[[^\]]+])|(f{1,2}|F{1,2})/g, (_, a, b) => {
            const B = b && b.toUpperCase();
            return a || formats[b] || englishFormats[b] || t(formats[B]) || t(englishFormats[B]);
        });
        return oldFormat.call(this, result);
    }
}