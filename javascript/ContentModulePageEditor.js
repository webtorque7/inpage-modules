(function ($) {
    //window.debug = true;

    $.entwine('ss.preview', function ($) {
        $('.content-module-page-editor').entwine({
            CurrentPreviewMode: null,

            onadd: function () {
                this._super();

                this.getPreview()
                    .removeClass('east')
                    .addClass('west');

                this.setupPreview();

            },
            onremove: function () {
                //restore preview position
                this.getPreview().removeClass('west').addClass('east');

                //restore previous preview mode
                window.localStorage.setItem('cms-preview-state-mode', this.getCurrentPreviewMode());
            },
            getPreview: function () {
                return $('.cms-preview');
            },
            getSizeControl: function () {
                return $('.cms-navigator .preview-size-selector');
            },
            getModeControl: function () {
                return $('.cms-navigator .preview-mode-selector');
            },
            onpreviewdirty: function () {
                this.getPreview()._initialiseFromContent();
            },
            setupPreview: function () {
                var navigator = this.find('.cms-navigator'),
                    preview = $('.cms-preview .cms-preview-controls');

                //todo get current mode from localStorage, restore when finished
                this.setCurrentPreviewMode(window.localStorage.getItem('cms-preview-state-mode'));

                window.localStorage.setItem('cms-preview-state-mode', 'preview');
                //preview.html(navigator.detach());
                preview.html(navigator.detach());

                this.getModeControl()
                    .hide()
                    .changeVisibleMode('preview');


                this.getSizeControl()
                    .changeVisibleSize('auto');

                //this.getPreview().enable();
                this.getPreview()._initialiseFromContent();
            }
        });
    });

    $.entwine('ss', function ($) {

        /**
         * Need this in different namespace
         */
        $('.content-module-page-editor').entwine({
            oneditmodule: function (e, data) {
                this.editModule(data.ID);
            },
            editModule: function (id) {
                var url = this.data('edit-module-url') + '/' + id;

                $('.cms-form-editor').loadForm(url);
            }
        });

    });

})(jQuery);