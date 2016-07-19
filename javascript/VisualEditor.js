(function ($) {
    //window.debug = true;

    $.entwine('ss', function ($) {
        $('.visual-editor').entwine({
            onadd: function () {
                this.hideMenuPanel();
                this._super();
            },
            redraw:function() {
                this._super();

            },
            hideMenuPanel: function() {
                setTimeout(function () {
                    $('.cms-panel.cms-menu').togglePanel(false, false, true);
                }, 50);
            },
            onpreviewdirty: function () {
                this.getPreview().reload();
            },
            getPreview: function() {
                return this.find('.visual-editor-preview');
            },
            oneditmodule: function (e, data) {
                this.editModule(data.ID);
            },
            editModule: function (id) {
                var url = this.data('edit-module-url') + '/' + id;
                $('.visual-editor-form').loadForm(url);
            }
        });
    });

})(jQuery);