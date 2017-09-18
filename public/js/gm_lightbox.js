(function($) {

    /**
     *  Display the light box (background/canvas/image)image on click.
     */
    function image_click() {

        $('.image_frame').find('a').on('click', function (e) {
            e.preventDefault();

            var elm = $(this);
            var thumb = elm.prev('img').attr('src');
            var image_url = get_full_image_url(thumb);

            set_active( elm );

            var info_obj = create_info_obj(elm);

            set_low_opacity_background();

            load_new_image(image_url, function() {

//                setTimeout(show_loading_gif, 500);
                show_loading_gif();

                create_canvas(rw, rh, image_url, info_obj);
            });

            navigate_arrows();
        })
    }

    /**
     * Append the spinner gif. This used the gm_js variable which is set in gm-community-gallery.php by the
     * gm_register_public_js() function.
     */
    function show_loading_gif()
    {
        if ( $(document).find('#gm_canvas').find('img').length < 1 )
        {
            var plugin_imgs_url = gm_js.images;
            var spinner_url = plugin_imgs_url + 'wpspin-2x.gif';
            var spinner_elm = '<div id="gm_spinner"><div id="gm_spin_wrapper"><img src="'+spinner_url+'"></div></div>';

            $('body').append(spinner_elm);
        }
    }

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
     * Create the canvas with embedded image. Acts as the callback for the load_new_image() function. Remove the loading
     * gif it's present.
     *
     * @param   {int}       rw      Loaded image height.
     * @param   {int}       rh      Loaded iage width.
     * @param   {string}    img_url URL for the image
     * @param   {object}    info_obj
     */
    function create_canvas(rw, rh, img_url, info_obj)
    {
        var window_h = $(window).height();
        var window_w = $(window).width();
        var max_h = window_h * .8;
        var min_w = window_w * .5;

        // Modify dimensions if the image is too big for the lightbox.
        if (rh > max_h)
        {
            var perc = (rh - max_h) / rh;

            rh = max_h;
            rw = Math.round( rw - (rw * perc) );
        }

        if (rw < min_w)
        {
            rw = min_w;
        }

        var top  = Math.round( (window_h - rh) / 2 );
        var left = Math.round( (window_w - rw) / 2 );

        var canvas = build_canvas_template(rw, rh, left, top, img_url, info_obj);

        $('body').append(canvas);
        if ( $(document).find('#gm_spinner').length > 0 )
        {
            $(document).find('#gm_spinner').remove();
        }

    }

    /**
     * Builds a canvas used to display the image being loaded. Width/Height and fixed position top/left are set by arguments.
     *
     * @param   {int} width
     * @param   {int} height
     * @param   {int} left
     * @param   {int} top
     * @param   {string} img_url     URL for the image being loaded and embedded in the #gm_canvas element.
     * @param   {object} info_obj
     * @returns {string}             HTML for the canvas with embedded image.
     */
    function build_canvas_template(width, height, left, top, img_url, info_obj)
    {
        var title     = info_obj.title;
        var submitter = info_obj.submitter;
        var message   = info_obj.message;
        var reply     = info_obj.reply;

        var title_bar = '<div id="gm_title_bar"><div id="gm_title">'+title+' by '+submitter+'</div><div id="gm_info_toggle">info +</div></div>';

        var message_cont = '<div id="gm_message_content"><div id="gm_message_text">'+message+'</div><div id="gm_reply_text">'+reply+'</div></div>';

        var replace_array = [width+'px', height+'px', left+'px', top+'px', img_url];
        var canvas_template = '<div id="gm_canvas" style="width: %s; height: %s; left: %s; top: %s;"><img src="%s"><div id="gm_img_close"><div id="gm_close">close [x]</div></div>'+ message_cont + title_bar +'</div></div></div></div>';

        while (replace_array.length > 0)
        {
            canvas_template = canvas_template.replace('%s', replace_array[0]);
            replace_array.splice(0, 1);
        }

        return canvas_template;
    }

    /**
     * Destroys the canvas/backdrop/arrows when the back drop is clicked.
     */
    function back_drop_click()
    {
        $(document).on('click', '#gm_drop_canvas', function(){
            image_close();
        });
    }

    /**
     * Destroys canvas/backdrop/arrows
     * @return {void}
     */
    function image_close() {
        var canvas    = $(document).find('#gm_canvas');
        var back_drop = $(document).find('#gm_drop_canvas');
        var img_nav   = $(document).find('#gm_img_nav');
        var spinner   = $(document).find('#gm_spinner');

        canvas.remove();
        back_drop.remove();
        img_nav.remove();
        spinner.remove();
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

        var target_anchor = target_img.find('a');

        $(document).find('#gm_canvas').remove();
        target_anchor.click();
    }

    /**
     * Append navigation arrows to the backdrop.
     */
    function navigate_arrows() {

        if ( $(document).find('#gm_img_nav').length < 1 )
        {
            var top = ( $(window).height() - 200) / 2;

            var nav_html = '<div id="gm_img_nav">';
            nav_html    += '<div id="gm_nav_left"><div class="gm_nav_arrow" style="top:'+top+'px"> <i class="fa fa-angle-left fa-3x" style="top:'+top+'px" aria-hidden="true"></i> </div></div>';
            nav_html    += '<div id="gm_nav_right"><div class="gm_nav_arrow" style="top:'+top+'px"> <i class="fa fa-angle-right fa-3x" style="top:'+top+'px" aria-hidden="true"></i> </div></div>';
            nav_html    += '</div>';

            $('#gm_drop_canvas').append(nav_html);
        }
    }

    /**
     * This function adds left/right navigation arrows when there are navigable images in the chosen direction.
     * Unnecessary since light/right navigation is now infinite.
     *
     * @deprecated  Lightbox is now infinite
     * @param anchor_elm
     */
    function old_navigate_arrows(anchor_elm) {

        var old_nav = $(document).find('#gm_img_nav');

        if (old_nav.length > 0)
        {
            old_nav.remove();
        }

        var parent = anchor_elm.closest('.image_card');

        var nav_html = '<div id="gm_img_nav">';

        var img_left  = parent.prev('.image_card');
        var img_right = parent.next('.image_card');

        var top = ( $(window).height() - 90) / 2;

        if ( is_empty(img_left) === false )
        {
            nav_html += '<div id="gm_nav_left"><div class="gm_nav_arrow" style="top:'+top+'px"> &lt; </div></div>';
        }
        if ( is_empty(img_right) === false )
        {
            nav_html += '<div id="gm_nav_right"><div class="gm_nav_arrow" style="top:'+top+'px"> &gt; </div></div>';
        }

        nav_html += '</div>';

        $('#gm_drop_canvas').append(nav_html);
    }

    /**
     * Left/Right navigation when .arrow_button elements are clicked.
     */
    function arrow_click()
    {
        $('body').on('click', function(e){
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

    function click_close()
    {
        $(document).on('click', '#gm_img_close', function() {
            image_close();
        });
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

    /* *********************
     * - Do lightbox things
     * *********************/

    image_click();
    arrow_click();
    info_click();
    click_close();
    back_drop_click();
    keyboard_nav();

})(jQuery);