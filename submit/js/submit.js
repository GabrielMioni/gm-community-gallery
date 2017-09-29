(function($) {

    function display_uploader() {

        var form = $(document).find('#gm-gallery');
        var new_input = '<input type="file" id="gm_file_input" name="gm_file_input">';
        var new_img_elm = '<img id="gm_preview_img" src="#" alt="your image" />';

        form.empty().append(new_input + new_img_elm);
    }

    function preview_img() {

        var input = $(document).find('#gm_file_input');

        input.on('change', function () {

            if (this.files && this.files[0])
            {
                var reader = new FileReader();

                reader.onload = function (e) {

                    var img_elm = $(document).find('#gm_preview_img');

                    img_elm.attr('src', e.target.result);
                };

                reader.readAsDataURL(this.files[0]);
            }
        })
    }

    display_uploader();
    preview_img();

})(jQuery);