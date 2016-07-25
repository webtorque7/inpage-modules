(function ($) {
    $.entwine('ss', function ($) {
        $('.visual-editor-preview').entwine({

            /**
             * Todo make this configurable
             */
            Sizes: {
                auto: {
                    width: '100%',
                    height: '100%'
                },
                mobile: {
                    width: '335px', // add 15px for approx desktop scrollbar
                    height: '568px'
                },
                mobileLandscape: {
                    width: '583px', // add 15px for approx desktop scrollbar
                    height: '320px'
                },
                tablet: {
                    width: '783px', // add 15px for approx desktop scrollbar
                    height: '1024px'
                },
                tabletLandscape: {
                    width: '1039px', // add 15px for approx desktop scrollbar
                    height: '768px'
                },
                desktop: {
                    width: '1024px',
                    height: '800px'
                }
            },

            onadd: function () {

            },
            redraw: function () {
                this._super();
            },
            reload: function() {
                this.setLoading();
                this.find('iframe').get(0).contentWindow.location.reload();
            },
            loadUrl: function (url) {
                var iframe = this.find('iframe'),
                    currentUrl = iframe.attr('src');

                if (url === currentUrl) { //just reload
                    this.reload();
                } else {
                    this.setLoading();
                    iframe.attr('src', url);
                }
            },
            changeSize: function (sizeName) {
                var sizes = this.getSizes(),
                    self = this;

                this.removeClass('auto desktop tablet mobile').addClass(sizeName);

                this.find('.visual-editor-preview-outer').animate({
                    width: sizes[sizeName].width,
                    height: sizes[sizeName].height
                }, 500);

                this.find('.visual-editor-preview-inner').animate({
                    width: sizes[sizeName].width
                }, 500, function(){
                    self.redraw();
                });

                return this;
            },
            setLoading:function() {
                this.css('opacity', 0.5);
            },
            setFinishedLoading:function() {
                this.css('opacity', 1)
            }
        });

        $('.visual-editor-preview iframe').entwine({
            onadd: function() {
                var preview = $('.visual-editor-preview');
                preview.setLoading();
                this.on('load', function(){
                    preview.setFinishedLoading();
                });
            }

        });

        $('.visual-editor-preview .cms-panel-content').entwine({
            redraw: function () {
                this._super();
            }
        });

        $('.visual-editor-preview .cms-navigator .switch .state-name').entwine({
            onclick: function (e) {
                e.preventDefault();

                this.parent().find('.active').removeClass('active');
                this.parent().find('input[checked]').prop('checked', false);

                this.next('label').addClass('active');
                this.prop('checked', true);

                $('.visual-editor-preview').loadUrl(this.data('link'));
            }
        });

        $('.visual-editor-preview .preview-size-selector select').entwine({
            /**
             * Trigger change in the preview size.
             */
            onchange: function (e) {
                e.preventDefault();

                var targetSizeName = $(this).val();
                $('.visual-editor-preview').changeSize(targetSizeName);
            }
        });
    });
})(jQuery);