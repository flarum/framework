/**
 * The icon classes of a model whose Font Awesome icon can be replaced by an
 * uploaded image, such as a group or a tag: `icon` unchanged when there is no
 * image, and with `IconImage IconImage--<n>` added when there is one (and
 * `IconImage--only` when there's no icon besides the image).
 *
 * Whatever renders the classes then shows the image, without knowing about
 * it: the Icon component, a Button's or a Badge's `icon`, even a plain
 * `<i className={icon}>`. The first time an image is seen, a rule for its
 * class is added to the page, setting `--icon-image`, which IconImage.less
 * paints in the glyph's place.
 *
 * A model with an icon image overrides its icon getter with it:
 *
 *     icon() {
 *       return withIconImage(Model.attribute<string | null>('icon').call(this), this.iconImageUrl(), this.iconImageSrcset());
 *     }
 */
export default function withIconImage<T extends string | null | undefined>(icon: T, url?: string | null, srcset?: string | null): T | string;
