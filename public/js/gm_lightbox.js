(function($) {

    /**
     *  Loads the image, builds/displays the image canvas and canvas wrapper, navigation buttons, low opacity backdrop.
     *  The clicked image's .image_card containing element is set with the 'gm_img_active' class.
     */
    function image_click() {

        $('.image_frame').find('a').on('click', function (e) {
            e.preventDefault();

            // While the lightbox is loading images, do not allow further navigation.
            if ( $(this).hasClass('loading') )
            {
                return false;
            }

            var elm = $(this);

            var thumb;

            var true_img_url = elm.parent().find('.gm_hidden_url');

            if (true_img_url.length > 0)
            {
                thumb = true_img_url.text();
            } else {
                thumb = elm.prev('img').attr('src');
            }

            var image_url = get_full_image_url(thumb);

            /* *********************************************************************************************************
             * - Add the 'gm_img_active' class to the containing .img_card for the image that's been clicked.
             * - The .gm_img_active class is used as a pointer when navigating the lightbox via keypress/swipe/arrow clicks
             * *********************************************************************************************************/
            set_active( elm );

            // Get text stuff from the image's hidden .gm_hidden_info element.
            var info_obj = create_info_obj(elm);

            set_low_opacity_background();

            load_new_image(image_url, function() {

                create_canvas(rw, rh, image_url, info_obj);
            });

            $('body').bind('touchmove', function(e){e.preventDefault()})
        })
    }

    /**
     * Returns an object that holds text/html that will be displayed in the animated 'info' box when an image
     * is viewed in the lightbox.
     *
     * @param    elm      The containing .image_card element that holds the .gm_hidden_info element from which data is gathered
     * @returns  {Object} Object holding text/html used to display info about the the image.
     */
    function create_info_obj(elm)
    {
        var parent = elm.closest('.image_card');
        var hidden_info = parent.find('.gm_hidden_info');

        var title     = hidden_info.find('.gm_title').html();
        var submitter = hidden_info.find('.gm_submitter').html();
        var message   = hidden_info.find('.gm_message').html();
        var reply     = hidden_info.find('.gm_reply').html();

        var info_obj = Object();

        info_obj.title     = title;
        info_obj.submitter = submitter;
        info_obj.message   = message;
        info_obj.reply     = reply;

        return info_obj;
    }

    /**
     * Sets/un-sets the 'gm_img_active' class on .image_card elements. Only one .image_card element can have the
     * 'gm_img_active' class at a time and the class acts as a pointer for later JS functions.
     *
     * @param   {HTMLElement}   elm     The clicked <a>.
     */
    function set_active(elm)
    {
        var active = $(document).find('.gm_img_active');

        if (active.length > 0)
        {
            active.removeClass('gm_img_active');
        }

        var image_container = elm.closest('.image_card');
        image_container.addClass('gm_img_active');
    }

    /**
     * Accepts the URL for the thumbnail image and returns the URL for the full sized image.
     *
     * @param   {string}    thumb   The original URL for the thumbnail that's been clicked.
     * @returns {string}            The URL for the full sized image.
     */
    function get_full_image_url(thumb) {

        var url   = strip_query_string(thumb);
        var file_name = url.split('/').pop();

        var thumb_dir = '/thumbs/' + file_name;
        var image_dir = '/images/' + file_name;

        return url.replace(thumb_dir, image_dir);
    }

    /**
     * Returns a URL without a query string.
     * @param   {string}    url     The original URL
     * @returns {string}    url     URL sans-query string
     */
    function strip_query_string(url) {
        return url.split("?")[0];
    }

    /**
     * Append the the low opacity background IF it's not already on the page.
     */
    function set_low_opacity_background()
    {
        var backdrop_elm = $(document).find('#gm_drop_canvas');

        if ( backdrop_elm.length < 1 )
        {
            var drop_canvas = "<div id='gm_drop_canvas'></div>";
            $(document).find('body').append(drop_canvas);
        }
    }

    /**
     * Load the image for the thumbnail that's been clicked and grab width/height values
     *
     * @param   {string}    img_url     The URL for the image being loaded.
     * @param               callback    Accepts an anonymous function that uses rw/rh.
     */
    function load_new_image(img_url, callback) {

        var new_image = new Image();
        $(new_image).attr('src', img_url);

        //  create an event handler that runs when the image has loaded
        $(new_image).on('load', function() {
            rw = new_image.width;
            rh = new_image.height;
            callback();
        });
    }

    /**
     * Either creates and appends a new canvas if one doesn't exist or causes the existing
     *
     * @param   {int}       rw      Loaded image height.
     * @param   {int}       rh      Loaded iage width.
     * @param   {string}    img_url URL for the image
     * @param   {object}    info_obj
     */
    function create_canvas(rw, rh, img_url, info_obj)
    {
        var is_mobile = check_mobile();

        var window_h = window.innerHeight;
        var window_w = window.innerWidth;

        var max_h = is_mobile === true ? window_h * .8 : window_h * .7;
        var max_w = is_mobile === true ? window_w * .9 : window_w * .4;

        // Modify dimensions if the image is too big for the lightbox.
        if (rh > max_h)
        {
            var perc_h = (rh - max_h) / rh;

            rh = max_h;
            rw = Math.round( rw - (rw * perc_h) );
        }

        if (rw > max_w)
        {
            var perc_w = (rw - max_w) / rw;

            rw = max_w;
            rh = Math.round( rh - (rh * perc_w) );
        }

        if (rw < max_w)
        {
            rw = max_w;
        }

        var top  = Math.round( (window_h - rh) / 2 );

        var set_w = rw.toString() + 'px';
        var set_h = rh.toString() + 'px';
        var set_t = top.toString() + 'px';

        if ( $(document).find('#gm_canvas').length < 1 || is_mobile === true )
        {
            var canvas = build_canvas_template(set_w, set_h, set_t, img_url, info_obj);

            var body = $('body');

            body.append(canvas);

            navigate_arrows();
        } else {

            animate_canvas(rw, rh, top, img_url, info_obj);
        }
    }

    /**
     * Builds a canvas used to display the image being loaded. Width/Height and fixed position top/left are set by arguments.
     *
     * @param   {string} width
     * @param   {string} height
     * @param   {string} top
     * @param   {string} img_url     URL for the image being loaded and embedded in the #gm_canvas element.
     * @param   {object} info_obj
     * @returns {string}             HTML for the canvas with embedded image.
     */
    function build_canvas_template(width, height, top, img_url, info_obj)
    {
        var title     = info_obj.title;
        var submitter = info_obj.submitter;
        var message   = info_obj.message;
        var reply     = info_obj.reply;

        var title_bar = '<div id="gm_title_bar"><div id="gm_title">'+title+' by '+submitter+'</div><div id="gm_info_toggle">info +</div></div>';

        var reply_cont = reply === '<p></p>' ? '' : '<div id="gm_reply_text">'+reply+'</div>';

        var message_cont = '<div id="gm_message_content"><div id="gm_message_text">'+message+'</div>'+reply_cont+'</div>';

        var replace_array = [width, height, img_url];
        var canvas_template = '<div id="gm_canvas" style="width: %s; height: %s;"><img src="%s"><div id="gm_img_close"><div id="gm_close">close [x]</div></div>'+ message_cont + title_bar +'</div></div></div></div>';

        while (replace_array.length > 0)
        {
            canvas_template = canvas_template.replace('%s', replace_array[0]);
            replace_array.splice(0, 1);
        }

        return '<div id="gm_canvas_wrapper" class="gm_swipe_area" style="top: '+top+'">' + canvas_template + '</div>';
    }

    /**
     * Used when the canvas is already on the screen. Empties the canvas, re-sizes canvas for new image, displays
     * a loading spinner and populates with new content.
     *
     * @param   {string} width
     * @param   {string} height
     * @param   {string} top
     * @param   {string} img_url     URL for the image being loaded and embedded in the #gm_canvas element.
     * @param   {object} info_obj
     */
    function animate_canvas(width, height, top, img_url, info_obj) {

        var canvas = $(document).find('#gm_canvas');
        var wrapper = $(document).find('#gm_canvas_wrapper');
        var img_link = $(document).find('.gm_image_hover');
        var animate_speed = 200;

        var loading_spinner = '<i class="fa fa-circle-o-notch fa fast-spin fa-fw fa-3x"></i><span class="sr-only">Loading...</span>';

        canvas.empty();

        // The 'loading' class prevents the image link from being navigated to while it's present.
        $.each(img_link, function () {
            $(this).addClass('loading');
        });

        canvas.animate({
                'width': width,
                'height': height
            }, animate_speed, function () {
            if ( canvas.find('.fa').length < 1 )
            {
                canvas.append(loading_spinner);
            }
        });

        wrapper.animate({
            'top' : top
        }, animate_speed, function() {

            var title     = info_obj.title;
            var submitter = info_obj.submitter;
            var message   = info_obj.message;
            var reply     = info_obj.reply;

            var title_bar = '<div style="display:none" id="gm_title_bar"><div id="gm_title">'+title+' by '+submitter+'</div><div id="gm_info_toggle">info +</div></div>';
            var close_bar = '<div style="display:none" id="gm_img_close"><div id="gm_close">close [x]</div></div>';

            var reply_cont = reply === '<p></p>' ? '' : '<div id="gm_reply_text">'+reply+'</div>';

            var message_cont = '<div id="gm_message_content"><div id="gm_message_text">'+message+'</div>'+reply_cont+'</div>';

            var img = '<img style="display:none" src="'+img_url+'">';

            setTimeout( function () {
                canvas.append(title_bar + close_bar + message_cont);
                canvas.append(img);
                canvas.find('.fa').remove();
                canvas.find('img').fadeIn();
                canvas.find('#gm_title_bar').fadeIn();
                canvas.find('#gm_img_close').fadeIn();

                $.each(img_link, function () {
                    // Allow lightbox navigation to continue
                    $(this).removeClass('loading');
                });

            }, 1000 );

        });
    }

    /**
     * Destroys the canvas/backdrop/arrows when the back drop is clicked.
     */
    function back_drop_click()
    {
        $(document).on('click', '#gm_drop_canvas, #gm_img_nav', function(){
            image_close();
        });
    }

    /**
     * Destroys canvas/backdrop/arrows
     * @return {void}
     */
    function image_close() {
        var canvas    = $(document).find('#gm_canvas');
        var wrapper   = $(document).find('#gm_canvas_wrapper');
        var back_drop = $(document).find('#gm_drop_canvas');
        var img_nav   = $(document).find('#gm_img_nav');
        var spinner   = $(document).find('#gm_spinner');

        wrapper.remove();
        canvas.remove();
        back_drop.remove();
        img_nav.remove();
        spinner.remove();

        $('body').unbind('touchmove');
    }

    function info_click() {
        $(document).on('click', '#gm_info_toggle', function () {
            var toggle_button = $(this);

            var animate_height = null;

            if ( ! toggle_button.hasClass('clicked') )
            {
                toggle_button.addClass('clicked');
                animate_height = '100%';
                toggle_button.empty();
                toggle_button.append('info - ');
            } else {
                toggle_button.removeClass('clicked');
                toggle_button.empty();
                toggle_button.append('info +');
                animate_height = 0;
            }

            var canvas = toggle_button.closest('#gm_canvas');
            var message_cont =canvas.find('#gm_message_content');

            message_cont.animate({height: animate_height}, 250);
        })
    }

    /**
     * Listens for keypress on escape, left and right keys. Escape triggers image_close() function. Left/Right
     * key presses trigger the navigate() function.
     *
     * @return  {bool|void} If the backdrop isn't present, navigation is unnecessary. Return false.
     */
    function keyboard_nav()
    {
        $(document).keydown(function(e) {

            var backdrop = $(document).find('#gm_drop_canvas');

            // Navigation is only active when the backdrop is up.
            if (backdrop.length < 1)
            {
                return false;
            }

            // get keycode value
            var keycode = (e.keyCode ? e.keyCode : e.which);

            if (keycode === 27)
            {
                image_close();
            }

            // 37 = left and 39 = right
            if (keycode == 37 && keycode == 39)
            {
                return false;
            }

            navigate(keycode);
        });
    }

    /**
     * Uses the Tocca.js library at public/js/tocca/Tocca.min.js to handle left/right swipe navigation on mobile
     */
    function mobile_nav()
    {
        var body = $('body');
        body.css({'touch-action': 'none'});

        var is_mobile = check_mobile();

        if (is_mobile === true)
        {
            var hover_elms = $(document).find('.gm_image_hover');

            hover_elms.each( function () {
                $(this).removeClass('gm_image_hover');
            });
        } else {
            return false;
        }

        /* *************************************************
         * - swipeleft triggers navigate to the next image.
         * - swiperight find the previous image.
         * *************************************************/
        body.on('swipeleft', function () {
            swipe_animate(39);
        });

        body.on('swiperight', function () {
            swipe_animate(37);
        });
    }

    /**
     * Slides the #gm_canvas_wrapper element left or right outside of the screen's width and then simulates a
     * click on the navigable image in the chosen direction.
     *
     * @param   keycode     Passed to the navigate() function used as a callback for the animate() call.
     * @returns {boolean}   False if the #gm_canvas isn't present. Else, executes animation.
     */
    function swipe_animate(keycode)
    {
        if ( $(document).find('#gm_canvas').length < 1 )
        {
            return false;
        }

        var canvas_wrapper = $(document).find('#gm_canvas');
        var animate_rules;

        var window_w = window.innerWidth + 50;

        switch (keycode)
        {
            case 39:
                animate_rules = {'right': window_w};
                break;
            case 37:
                animate_rules = {'left': window_w};
                break;
            default:
                animate_rules = null;
        }

        if ( animate_rules !== null )
        {
            canvas_wrapper.animate( animate_rules, function () {
                navigate(keycode);
                $(document).find('#gm_canvas').remove();
            });
        }
    }

    /**
     * Tries to navigate to the previous (left) or next (right) navigable image. If there are no navigable images
     * in the direction chosen, nothing happens. Else, destroy the current canvas/image and replace with the
     * new image that's been navigated to.
     *
     * @param   {int}   keycode     Sets whether navigation is going prev() or next(). Left = 37, Right = 39
     */
    function navigate(keycode) {

        var active = $(document).find('.gm_img_active');
        var target_img = null;

        switch (keycode)
        {
            case 37:
                target_img = active.prev('.image_card');
                break;
            case 39:
                target_img = active.next('.image_card');
                break;
            default:
                break;
        }

        var empty_check = is_empty(target_img);

        // If there was no navigable target_img in the chosen direction, left -> last image / right -> first iamge.
        if (empty_check !== false)
        {
            var gallery = $('#gm_community_gallery');

            switch (keycode)
            {
                case 37:
                    target_img = gallery.find('.image_card:last');
                    break;
                case 39:
                    target_img = gallery.find('.image_card:first');
                    break;
                default:
                    break;
            }
        }

        if (target_img !== null)
        {
            var target_anchor = target_img.find('a');

            target_anchor.click();
        }
    }

    /**
     * Append navigation arrows to the backdrop.
     */
    function navigate_arrows() {


        if ( $(document).find('#gm_img_nav').length < 1 )
        {
            var top = ( $(window).height() - 200) / 2;

            var nav_html = '<div id="gm_img_nav">';
            nav_html    += '<div id="gm_nav_left" style="top:'+top+'px""><div class="gm_nav_arrow"> <i class="fa fa-angle-left fa-3x" aria-hidden="true"></i> </div></div>';
            nav_html    += '<div id="gm_nav_right" style="top:'+top+'px""><div class="gm_nav_arrow"> <i class="fa fa-angle-right fa-3x" aria-hidden="true"></i> </div></div>';
            nav_html    += '</div>';

            $('#gm_canvas_wrapper').append(nav_html);
        }
    }

    /**
     * Left/Right navigation when .arrow_button elements are clicked.
     */
    function arrow_click()
    {
        $('body').on('click', function(e) {
            if ( $(e.target).is('.gm_nav_arrow') || $(e.target).is('.gm_nav_arrow .fa') )
            {
                // Stop bubble to backdrop so the canvas/backdrop/arrows aren't closed
                e.stopPropagation();
                var elm = e.target;
                var parent = $(elm).closest('.gm_nav_arrow').parent();
                var id = $(parent).attr('id');

                switch (id)
                {
                    case 'gm_nav_left':
                        navigate(37);
                        break;
                    case 'gm_nav_right':
                        navigate(39);
                        break;
                    default:
                        break;
                }
            }
        });
    }

    /**
     * Trigger's image_close when the '[x] close' button on the lightbox is clicked.
     */
    function click_close()
    {
        $(document).on('click', '#gm_img_close', function() {
            image_close();
        });
    }

    /**
     * Destroys and rebuilds the currently viewed lightbox image on portrait/landscape switch. Otherwise the lightbox
     * would stay the same size and wouldn't fit the screen.
     */
    function orientation_change()
    {
        window.addEventListener( 'orientationchange', function() {

            var canvas = $(document).find('#gm_canvas_wrapper');

            if ( canvas.length < 1 )
            {
                return false;
            }

            var active = $(document).find('.gm_img_active');
            var active_a = active.find('a');

            active.removeClass('gm_img_active');
            active_a.click();
        }, false);
    }

    /**
     * Checks if the element/object passed in the argument is empty
     *
     * @param   {*}         str     The element/object being checked.
     * @returns {boolean}           True if empty, else false.
     */
    function is_empty(str)
    {
        return (!str || 0 === str.length);
    }

    /**
     * Checks the value at gm_js.is_mobile, which is set in the gm-communit-gallery.php page using wp_is_mobile()
     *
     * @returns     {boolean}   Returns true if wp_is_mobile() returned true. Else, false.
     */
    function check_mobile()
    {
        var is_mobile = parseInt( gm_js.is_mobile );

        switch ( is_mobile )
        {
            case 1:
                return true;
                break;
            case 0:
                return false;
                break;
            default:
                return false;
                break;
        }
    }

    /* *********************
     * - Do lightbox things
     * *********************/

    image_click();
    arrow_click();
    info_click();
    click_close();
    back_drop_click();
    keyboard_nav();
    mobile_nav();
    orientation_change();

})(jQuery);