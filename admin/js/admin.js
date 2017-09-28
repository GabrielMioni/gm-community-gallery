(function($) {

    function check_all() {

        var image_cards = $(document).find('.image_card');

        $(document).on('click', 'input[name="gm_bulk_action"]', function () {

            var set_check = this.checked;

            image_cards.each(function () {
                $(this).find('input[type="checkbox"]').prop('checked', set_check);
            });
        });
    }

    check_all();

})(jQuery);