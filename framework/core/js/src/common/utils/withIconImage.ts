/** The class each image's rule is keyed on, by the image's URL and srcset. */
const classNames = new Map<string, string>();

let sheet: CSSStyleSheet | null | undefined;
let imageSetSupported: boolean | undefined;

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
export default function withIconImage<T extends string | null | undefined>(icon: T, url?: string | null, srcset?: string | null): T | string {
  if (!url) return icon;

  return [icon, 'IconImage', iconImageClass(url, srcset), !icon && 'IconImage--only'].filter(Boolean).join(' ');
}

function iconImageClass(url: string, srcset?: string | null): string {
  const key = `${url}\n${srcset || ''}`;
  let className = classNames.get(key);

  if (!className) {
    className = `IconImage--${classNames.size + 1}`;
    classNames.set(key, className);
    addRules(className, imageValue(url, srcset));
  }

  return className;
}

function addRules(className: string, image: string): void {
  const stylesheet = getStylesheet();

  if (!stylesheet) return;

  const declaration = `{ --icon-image: ${image}; }`;

  // Separate rules, so that a browser without :has() still shows the image,
  // in the badge's icon slot rather than filling the badge.
  for (const selector of [`.${className}`, `.Badge:has(> .${className})`]) {
    try {
      stylesheet.insertRule(`${selector} ${declaration}`, stylesheet.cssRules.length);
    } catch {
      // An unsupported selector: that rule only.
    }
  }
}

function getStylesheet(): CSSStyleSheet | null {
  if (sheet === undefined) {
    if (typeof document === 'undefined') return null;

    const style = document.createElement('style');
    style.setAttribute('data-icon-images', '');
    document.head.appendChild(style);
    sheet = style.sheet;
  }

  return sheet;
}

/**
 * The CSS image: an image-set() of the srcset's density variants, or the
 * plain URL.
 */
function imageValue(url: string, srcset?: string | null): string {
  const candidates = (srcset || '')
    .split(/,\s+/)
    .map((candidate) => candidate.trim().split(/\s+/))
    .filter(([src]) => src);

  // image-set() takes densities only, not srcset's width descriptors.
  if (candidates.length && candidates.every(([, density = '1x']) => /^\d+(\.\d+)?x$/.test(density)) && supportsImageSet()) {
    return `image-set(${candidates.map(([src, density = '1x']) => `${cssUrl(src)} ${density}`).join(', ')})`;
  }

  return cssUrl(url);
}

function supportsImageSet(): boolean {
  if (imageSetSupported === undefined) {
    imageSetSupported = typeof CSS !== 'undefined' && !!CSS.supports?.('background-image', 'image-set(url("x.png") 1x)');
  }

  return imageSetSupported;
}

function cssUrl(url: string): string {
  return `url("${url.replace(/[\n\r\f]/g, '').replace(/["\\]/g, '\\$&')}")`;
}
