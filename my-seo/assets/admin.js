jQuery(function($){
    // Счётчик символов для title/description
    $('.my-seo-box input[name="seo_title"], .my-seo-box textarea[name="seo_description"]').on('input', function(){
        var max = parseInt($(this).attr('maxlength'), 10);
        var len = $(this).val().length;
        var $counter = $(this).siblings('.my-seo-counter');
        if (!$counter.length) {
            $counter = $('<span class="my-seo-counter" style="margin-left:8px;font-size:12px;"></span>');
            $(this).after($counter);
        }
        $counter.text(len + '/' + max).css('color', len > max * 0.9 ? '#d63638' : '#666');
    }).trigger('input');
});