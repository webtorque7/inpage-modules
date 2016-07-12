(function($){
    $.entwine('ss', function($){
        $('#content-module-page-editor-toolbox').entwine({
            ondrag:function(e) {
                if (e.originalEvent.clientX > 0 && e.originalEvent.clientY > 0) {
                    this.css({
                        left: e.originalEvent.clientX > 0 ? e.originalEvent.clientX : 0,
                        top: e.originalEvent.clientY > 0 ? e.originalEvent.clientY : 0
                    });
                }
            }
        });

        $('#content-module-page-editor-toolbox a').entwine({
            onclick:function(e) {
                e.preventDefault();

                $('#content-module-page-editor-cms-form-editor').loadForm(this.attr('href'));
            }
        });
    });
})(jQuery);