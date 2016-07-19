(function ($) {
    $.entwine('ss', function ($) {


        $('.visual-editor-form').entwine({
            BrowseHistory: [],

            onadd: function () {
            },
            /**
             * Set updateHistory to true if navigation within a form, e.g. gridfield, otherwise we clear the history when
             * a new form is loaded
             *
             * @param url
             * @param updateHistory
             */
            loadForm: function (url, updateHistory) {
                var self = this;

                $.ajax({
                    url: url,
                    method: 'GET',
                    type: 'GET',
                    global: false,
                    success: function (data) {
                        //ajaxComplete is causing this to show again
                        $('.visual-editor').hideMenuPanel();

                        //if navigating within a form (gridfield) update history
                        if (updateHistory) {
                            self.getBrowseHistory().push(url);
                        } else { //if loading a new page clear history
                            self.setBrowseHistory([url]);
                        }

                        if (data.Status) { //got a json object
                            if (data.Message) {
                                statusMessage(data.Message);
                            }

                            self.showForm(data.Content);
                        } else {
                            self.showForm(data);
                        }

                    }
                });
            },
            showForm: function (form) {
                var self = this;

                if (!this.is(':visible')) {
                    this.updateContent(form);
                    this.slideOut();
                } else {
                    this.animate({opacity: 0.5}, 'fast', function () {
                        self.updateContent(form);
                        self.animate({opacity: 1}, 1000);
                        self.find('.cms-panel-content').redraw();
                        self.find('.cms-panel-content').children().redraw();
                    });
                }
            },
            hideForm: function (callback) {
                var self = this,
                    container = $('.visual-editor'),
                    fullWidth = container.width();

                this.css({
                    left: 'auto',
                    right: 0
                }).stop().animate({
                    width: 0
                }, 1000, 'easeInOutQuad', function(){
                    self.hide();
                    //clear content so onremove is triggered
                    self.updateContent('');
                });

                //need to adjust children as well as they are fixed width
                $('.visual-editor-preview')
                    .children()
                    .stop()
                    .animate({
                        width: fullWidth
                    }, 1000, 'easeInOutQuad');

                $('.visual-editor-preview')
                    .removeClass('west')
                    .addClass('center')
                    .stop()
                    .animate({
                        width: fullWidth
                    }, 1000, 'easeInOutQuad', function(){
                        $('.cms-container').redraw();
                        $('.visual-editor-toolbox').trigger('previewresized');
                    });
            },
            slideOut: function () {
                var self = this,
                    preview = $('.visual-editor-preview'),
                    initialWidth = preview.width(),
                    newWidth = Math.floor(initialWidth/2);

                //show so we can get the width, max-width should limit it
                this.css({
                    visibility: 'hidden',
                    display: 'block',
                    width: newWidth
                });

                var formWidth = this.width(),
                    previewWidth = initialWidth - formWidth;

                //shrink preview window
                $('.visual-editor-preview')
                    .stop()
                    .removeClass('center')
                    .addClass('west')
                    .animate({
                        width: previewWidth
                    }, 1000, 'easeInOutQuad', function(){
                        $('.visual-editor-toolbox').trigger('previewresized');
                    });

                //need to adjust children as well as they are fixed width
                $('.visual-editor-preview')
                    .children()
                        .stop()
                        .animate({
                            width: previewWidth
                        }, 1000, 'easeInOutQuad');

                //expand form
                this.stop().css({
                    visibility: 'visible',
                    width: 0,
                    left: 'auto',
                    right: 0 //set right so we slide out from right side of screen
                }).animate({
                    width: formWidth
                }, 1000, 'easeInOutQuad', function(){
                    self.css({
                       right: 'auto' //reset so layout can set left
                    });
                    $('.cms-container').redraw();
                });
            },
            updateContent: function (content) {
                this.find('.form').html(content);

                //check back button
                if (this.getBrowseHistory().length > 1) {
                    this.find('.back').show();
                } else {
                    this.find('.back').hide();
                }

            },
            goBack: function () {
                //need to pop twice as current url is top of the stack
                this.getBrowseHistory().pop();
                var url = this.getBrowseHistory().pop();

                if (url) {
                    this.loadForm(url);
                }
            },
            refreshPreview: function () {
                $('.visual-editor').trigger('previewdirty');
            }

        });

        $('.visual-editor-form .cms-content-header .back').entwine({
            onclick: function (e) {
                e.preventDefault();

                $('.visual-editor-form').goBack();
            }
        });

        $('.visual-editor-form .cms-content-header .close').entwine({
            onclick: function (e) {
                e.preventDefault();

                $('.visual-editor-form').hideForm();
            }
        });

        $('.visual-editor-form .cms-content-header .refresh').entwine({
            onclick: function (e) {
                e.preventDefault();

                $('.visual-editor-form ').refreshPreview();
            }
        });

        //need to be more specific than LeftAndMain to overwrite default behaviour
        var buttonSelector = 'body .visual-editor-form form .Actions input[type=submit],' +
                'body .visual-editor-form form.cms-edit-form .Actions input[type=submit],' +
                'body .visual-editor-form form .Actions button.action,' +
                'body .visual-editor-form form.cms-edit-form .Actions button.action';

        $(buttonSelector).entwine({
            onclick: function (e) {
                e.preventDefault();
                e.stopImmediatePropagation();

                //append action so SS correctly picks it up
                var container = this.closest('.visual-editor-form'),
                    form = this.closest('form'),
                    data = form.serialize() + '&' + this.attr('name') + '=' + '1',
                    self = this;

                this.addClass('loading');
                $.ajax({
                    url: form.attr('action'),
                    method: 'POST',
                    type: 'POST',
                    data: data,
                    global: false,
                    success: function (data, message, xhr) {

                        self.removeClass('loading');
                        if (data.Status) {
                            if (data.Message) {
                                statusMessage(data.Message, 'good');
                            }

                            if (data.Content) {
                                container.updateContent(data.Content);
                            }
                            $('.visual-editor-form ').refreshPreview();
                        } else {
                            if (data.Message) {
                                statusMessage(data.Message, 'bad');
                            } else if (data) { //we have html
                                container.updateContent(data);
                                $('.visual-editor-form ').refreshPreview();
                            }

                            //handle redirects in the headers
                            var url = xhr.getResponseHeader('X-ControllerURL');

                            if (url) {
                                container.loadForm(url);
                                $('.visual-editor-form ').refreshPreview();
                            }
                        }
                    }
                });
            }
        });

        //need an id to override default behaviour
        $('#visual-editor-form .ss-gridfield .ss-gridfield-item').entwine({
            onclick: function (e) {
                e.preventDefault();
                e.stopImmediatePropagation();

                var editLink = this.find('.edit-link');
                if (editLink.length) {
                    this.closest('.visual-editor-form').loadForm(editLink.attr('href'), true);
                }
            }
        });

        $('#visual-editor-form .ss-gridfield .ss-gridfield-buttonrow a, #visual-editor-form .form a').entwine({
            onclick: function (e) {

                //don't load empty links (tabs etc)
                if (!this.attr('href') || this.attr('href').indexOf('#') !== -1) {
                    this._super(e);
                    return;
                }

                e.preventDefault();
                e.stopImmediatePropagation();

                this.closest('.visual-editor-form').loadForm(this.attr('href'), true);

            }
        });

        /**
         * Trigger editing module on preview to highlight module being edited
         */
        $('.module-edit-form').entwine({
            onadd: function () {
                this.selectPreviewModule();
            },
            onremove: function () {
                this.getPreviewModules().removeClass('active');
            },
            /**
             * Message triggered from preview iframe, allows us to show active module when preview reloaded
             */
            onpreviewloaded: function () {
                this.selectPreviewModule();
            },
            /**
             * Selects the module inside the preview
             */
            selectPreviewModule: function () {
                var module = this.find('input[name=ID]').val(),
                    previewModules = this.getPreviewModules();

                previewModules
                    .removeClass('active')
                    .filter('[data-module-id=' + module + ']')
                    .addClass('active');
            },
            getPreviewModules: function () {
                return $($('.visual-editor-preview').find('iframe').get(0).contentDocument).find('.visual-editor-module-handler');
            }
        });
    });

})(jQuery);