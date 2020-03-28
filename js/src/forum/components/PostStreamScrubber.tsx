import Component from '../../common/Component';
import icon from '../../common/helpers/icon';
import ScrollListener from '../../common/utils/ScrollListener';
import SubtreeRetainer from '../../common/utils/SubtreeRetainer';
import formatNumber from '../../common/utils/formatNumber';
import PostStream from './PostStream';
import { EventHandler } from '../../common/utils/Evented';

/**
 * The `PostStreamScrubber` component displays a scrubber which can be used to
 * navigate/scrub through a post stream.
 */
export default class PostStreamScrubber extends Component {
    handlers: { [key: string]: EventHandler } = {};

    /**
     * The index of the post that is currently at the top of the viewport.
     */
    index: number = 0;

    /**
     * The number of posts that are currently visible in the viewport.
     */
    visible: number = 1;

    /**
     * The description to render on the scrubber.
     */
    description: string = '';

    // Define a handler to update the state of the scrollbar to reflect the
    // current scroll position of the page.
    scrollListener = new ScrollListener(this.onscroll.bind(this));

    // Create a subtree retainer that will always cache the subtree after the
    // initial draw. We render parts of the scrubber using this because we
    // modify their DOM directly, and do not want Mithril messing around with
    // our changes.
    subtree = new SubtreeRetainer(() => true);

    // When the mouse is pressed on the scrollbar handle, we capture some
    // information about its current position. We will store this
    // information in an object and pass it on to the document's
    // mousemove/mouseup events later.
    dragging = false;
    mouseStart = 0;
    indexStart = 0;

    // Added when Component is initialized through `oninit` prop
    stream!: PostStream;

    view() {
        const count = this.count();
        const unreadCount = this.stream?.discussion.unreadCount() || 0;
        const unreadPercent = count ? Math.min(count - this.index, unreadCount) / count : 0;

        const viewing = app.translator.transChoice('core.forum.post_scrubber.viewing_text', count, {
            index: (
                <span className="Scrubber-index" onbeforeupdate={() => this.subtree.update()}>
                    {formatNumber(Math.min(Math.ceil(this.index + this.visible), count))}
                </span>
            ),
            count: <span className="Scrubber-count">{formatNumber(count)}</span>,
        });

        function styleUnread(vnode) {
            const $element = $(vnode.dom);
            const newStyle = {
                top: 100 - unreadPercent * 100 + '%',
                height: unreadPercent * 100 + '%',
                display: unreadCount == 0 && 'none',
            };

            if (vnode.state.oldStyle) {
                $element.css(vnode.state.oldStyle).animate(newStyle);
            } else {
                $element.css(newStyle);
            }

            vnode.state.oldStyle = newStyle;
        }

        return (
            <div className={'PostStreamScrubber Dropdown ' + (this.disabled() ? 'disabled ' : '') + (this.props.className || '')}>
                <button className="Button Dropdown-toggle" data-toggle="dropdown">
                    {viewing} {icon('fas fa-sort')}
                </button>

                <div className="Dropdown-menu dropdown-menu">
                    <div className="Scrubber">
                        <a className="Scrubber-first" onclick={this.goToFirst.bind(this)}>
                            {icon('fas fa-angle-double-up')} {app.translator.trans('core.forum.post_scrubber.original_post_link')}
                        </a>

                        <div className="Scrubber-scrollbar">
                            <div className="Scrubber-before" />
                            <div className="Scrubber-handle">
                                <div className="Scrubber-bar" />
                                <div className="Scrubber-info">
                                    <strong>{viewing}</strong>
                                    <span className="Scrubber-description" onbeforeupdate={() => this.subtree.update()}>
                                        {this.description}
                                    </span>
                                </div>
                            </div>
                            <div className="Scrubber-after" />

                            <div className="Scrubber-unread" oncreate={styleUnread} onupdate={styleUnread}>
                                {app.translator.trans('core.forum.post_scrubber.unread_text', { count: unreadCount })}
                            </div>
                        </div>

                        <a className="Scrubber-last" onclick={this.goToLast.bind(this)}>
                            {icon('fas fa-angle-double-down')} {app.translator.trans('core.forum.post_scrubber.now_link')}
                        </a>
                    </div>
                </div>
            </div>
        );
    }

    /**
     * Go to the first post in the discussion.
     */
    goToFirst() {
        this.stream.goToFirst();
        this.index = 0;
        this.renderScrollbar(true);
    }

    /**
     * Go to the last post in the discussion.
     */
    goToLast() {
        this.stream.goToLast();
        this.index = this.count();
        this.renderScrollbar(true);
    }

    /**
     * Get the number of posts in the discussion.
     */
    count(): number {
        return this.stream?.count() || 0;
    }

    /**
     * When the stream is unpaused, update the scrubber to reflect its position.
     */
    streamWasUnpaused() {
        this.update(window.pageYOffset);
        this.renderScrollbar(true);
    }

    /**
     * Check whether or not the scrubber should be disabled, i.e. if all of the
     * posts are visible in the viewport.
     */
    disabled(): boolean {
        return this.visible >= this.count();
    }

    /**
     * When the page is scrolled, update the scrollbar to reflect the visible
     * posts.
     */
    onscroll(top: number) {
        const stream = this.stream;

        if (!stream || stream.paused || !stream.$()) return;

        this.update(top);
        this.renderScrollbar();
    }

    /**
     * Update the index/visible/description properties according to the window's
     * current scroll position.
     */
    update(scrollTop: number = 0) {
        const stream = this.stream;

        const marginTop = stream.getMarginTop();
        const viewportTop = scrollTop + marginTop;
        const viewportHeight = $(window).height() - marginTop;

        // Before looping through all of the posts, we reset the scrollbar
        // properties to a 'default' state. These values reflect what would be
        // seen if the browser were scrolled right up to the top of the page,
        // and the viewport had a height of 0.
        const $items = stream.$('.PostStream-item[data-index]');
        let index = $items.first().data('index') || 0;
        let visible = 0;
        let period = '';

        // Now loop through each of the items in the discussion. An 'item' is
        // either a single post or a 'gap' of one or more posts that haven't
        // been loaded yet.
        $items.each(function (this: HTMLElement) {
            const $this = $(this);
            const top = $this.offset().top;
            const height = $this.outerHeight(true);

            // If this item is above the top of the viewport, skip to the next
            // one. If it's below the bottom of the viewport, break out of the
            // loop.
            if (top + height < viewportTop) {
                return true;
            }
            if (top > viewportTop + viewportHeight) {
                return false;
            }

            // Work out how many pixels of this item are visible inside the viewport.
            // Then add the proportion of this item's total height to the index.
            const visibleTop = Math.max(0, viewportTop - top);
            const visibleBottom = Math.min(height, viewportTop + viewportHeight - top);
            const visiblePost = visibleBottom - visibleTop;

            if (top <= viewportTop) {
                index = parseFloat($this.data('index')) + visibleTop / height;
            }

            if (visiblePost > 0) {
                visible += visiblePost / height;
            }

            // If this item has a time associated with it, then set the
            // scrollbar's current period to a formatted version of this time.
            const time = $this.data('time');
            if (time) period = time;

            return true;
        });

        this.index = index;
        this.visible = visible;
        this.description = period ? dayjs(period).format('MMMM YYYY') : '';
    }

    onremove(vnode) {
        super.onremove(vnode);

        this.ondestroy();
    }

    oncreate(vnode) {
        super.oncreate(vnode);

        // When the post stream begins loading posts at a certain index, we want our
        // scrubber scrollbar to jump to that position.
        this.stream.on('unpaused', (this.handlers.streamWasUnpaused = this.streamWasUnpaused.bind(this)));
        this.stream.on('update', () => this.update());

        this.scrollListener.start();

        // Whenever the window is resized, adjust the height of the scrollbar
        // so that it fills the height of the sidebar.
        $(window)
            .on('resize', (this.handlers.onresize = this.onresize.bind(this)))
            .resize();

        // When any part of the whole scrollbar is clicked, we want to jump to
        // that position.
        this.$('.Scrubber-scrollbar')
            .on('click', this.onclick.bind(this))

            // Now we want to make the scrollbar handle draggable. Let's start by
            // preventing default browser events from messing things up.
            .css({ cursor: 'pointer', 'user-select': 'none' })
            .on('dragstart mousedown touchstart', (e) => e.preventDefault());

        this.$('.Scrubber-handle')
            .css('cursor', 'move')
            .on('mousedown touchstart', this.onmousedown.bind(this) as ZeptoEventHandler)

            // Exempt the scrollbar handle from the 'jump to' click event.
            .click((e) => e.stopPropagation());

        // When the mouse moves and when it is released, we pass the
        // information that we captured when the mouse was first pressed onto
        // some event handlers. These handlers will move the scrollbar/stream-
        // content as appropriate.
        $(document)
            .on('mousemove touchmove', (this.handlers.onmousemove = this.onmousemove.bind(this) as ZeptoEventHandler))
            .on('mouseup touchend', (this.handlers.onmouseup = this.onmouseup.bind(this)));
    }

    ondestroy() {
        this.scrollListener.stop();

        this.stream.off('unpaused', this.handlers.streamWasUnpaused);

        $(window).off('resize', this.handlers.onresize);

        $(document).off('mousemove touchmove', this.handlers.onmousemove).off('mouseup touchend', this.handlers.onmouseup);
    }

    /**
     * Update the scrollbar's position to reflect the current values of the
     * index/visible properties.
     */
    renderScrollbar(animate?: boolean) {
        const percentPerPost = this.percentPerPost();
        const index = this.index;
        const count = this.count();
        const visible = this.visible || 1;

        const $scrubber = this.$();
        $scrubber.find('.Scrubber-index').text(formatNumber(Math.min(Math.ceil(index + visible), count)));
        $scrubber.find('.Scrubber-description').text(this.description);
        $scrubber.toggleClass('disabled', this.disabled());

        const heights: { before?: number; handle?: number; after?: number } = {};
        heights.before = Math.max(0, percentPerPost.index * Math.min(index, count - visible));
        heights.handle = Math.min(100 - heights.before, percentPerPost.visible * visible);
        heights.after = 100 - heights.before - heights.handle;

        const func = animate ? 'animate' : 'css';
        for (const part in heights) {
            const $part = $scrubber.find(`.Scrubber-${part}`);
            $part[func]({ height: heights[part] + '%' }, 'fast');

            // jQuery likes to put overflow:hidden, but because the scrollbar handle
            // has a negative margin-left, we need to override.
            if (func === 'animate') $part.css('overflow', 'visible');
        }
    }

    /**
     * Get the percentage of the height of the scrubber that should be allocated
     * to each post.
     *
     * @property index The percent per post for posts on either side of
     *     the visible part of the scrubber.
     * @property visible The percent per post for the visible part of the
     *     scrubber.
     */
    percentPerPost(): { index: number; visible: number } {
        const count = this.count() || 1;
        const visible = this.visible || 1;

        // To stop the handle of the scrollbar from getting too small when there
        // are many posts, we define a minimum percentage height for the handle
        // calculated from a 50 pixel limit. From this, we can calculate the
        // minimum percentage per visible post. If this is greater than the actual
        // percentage per post, then we need to adjust the 'before' percentage to
        // account for it.
        const minPercentVisible = (50 / this.$('.Scrubber-scrollbar').outerHeight()) * 100;
        const percentPerVisiblePost = Math.max(100 / count, minPercentVisible / visible);
        const percentPerPost = count === visible ? 0 : (100 - percentPerVisiblePost * visible) / (count - visible);

        return {
            index: percentPerPost,
            visible: percentPerVisiblePost,
        };
    }

    onresize() {
        this.scrollListener.update();

        // Adjust the height of the scrollbar so that it fills the height of
        // the sidebar and doesn't overlap the footer.
        const scrubber = this.$();
        const scrollbar = this.$('.Scrubber-scrollbar');

        scrollbar.css(
            'max-height',
            $(window).height() -
                scrubber.offset().top +
                $(window).scrollTop() -
                parseInt($('#app').css('padding-bottom'), 10) -
                (scrubber.outerHeight() - scrollbar.outerHeight())
        );
    }

    onmousedown(e: MouseEvent) {
        this.mouseStart = window.TouchEvent && e instanceof TouchEvent ? e.touches[0].clientY : e.clientY;
        this.indexStart = this.index;
        this.dragging = true;
        this.stream.paused = true;
        $('body').css('cursor', 'move');
    }

    onmousemove(e: MouseEvent) {
        if (!this.dragging) return;

        let y = window.TouchEvent && e instanceof TouchEvent ? e.touches[0].clientY : e.clientY;

        // Work out how much the mouse has moved by - first in pixels, then
        // convert it to a percentage of the scrollbar's height, and then
        // finally convert it into an index. Add this delta index onto
        // the index at which the drag was started, and then scroll there.
        const deltaPixels = y - this.mouseStart;
        const deltaPercent = (deltaPixels / this.$('.Scrubber-scrollbar').outerHeight()) * 100;
        const deltaIndex = deltaPercent / this.percentPerPost().index || 0;
        const newIndex = Math.min(this.indexStart + deltaIndex, this.count() - 1);

        this.index = Math.max(0, newIndex);
        this.renderScrollbar();
    }

    onmouseup() {
        if (!this.dragging) return;

        this.mouseStart = 0;
        this.indexStart = 0;
        this.dragging = false;
        $('body').css('cursor', '');

        this.$().removeClass('open');

        // If the index we've landed on is in a gap, then tell the stream-
        // content that we want to load those posts.
        const intIndex = Math.floor(this.index);
        this.stream.goToIndex(intIndex);
        this.renderScrollbar(true);
    }

    onclick(e) {
        // Calculate the index which we want to jump to based on the click position.

        // 1. Get the offset of the click from the top of the scrollbar, as a
        //    percentage of the scrollbar's height.
        const $scrollbar = this.$('.Scrubber-scrollbar');
        const offsetPixels = (e.clientY || e.originalEvent.touches[0].clientY) - $scrollbar.offset().top + $('body').scrollTop();
        let offsetPercent = (offsetPixels / $scrollbar.outerHeight()) * 100;

        // 2. We want the handle of the scrollbar to end up centered on the click
        //    position. Thus, we calculate the height of the handle in percent and
        //    use that to find a new offset percentage.
        offsetPercent = offsetPercent - parseFloat($scrollbar.find('.Scrubber-handle')[0].style.height) / 2;

        // 3. Now we can convert the percentage into an index, and tell the stream-
        //    content component to jump to that index.
        let offsetIndex = offsetPercent / this.percentPerPost().index;
        offsetIndex = Math.max(0, Math.min(this.count() - 1, offsetIndex));
        this.stream.goToIndex(Math.floor(offsetIndex));
        this.index = offsetIndex;
        this.renderScrollbar(true);

        this.$().removeClass('open');
    }
}
