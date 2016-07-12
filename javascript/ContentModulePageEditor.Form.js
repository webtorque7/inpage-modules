(function ($) {
    $.entwine('ss', function ($) {


        $('#content-module-page-editor-cms-form-editor').entwine({
            BrowseHistory: [],

            onadd: function () {
            },
            /**
             * Set updateHistory to true if navigation within a form, e.g. gridfield, else we clear the history when
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
                    global: false,
                    success: function (data) {
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
                    self.updateContent(form);
                    this.addClass('loading').animate({opacity: 0.5}, 'fast', function () {
                        self.updateContent(form);
                        self.animate({opacity: 1}, 1000);
                    });
                }
            },
            hideForm: function (callback) {
                var self = this,
                    width = this.outerWidth();

                $('.preview-scroll').stop().animate({
                    width: '100%'
                }, 'fast');

                this.stop().animate({
                    right: width * -1
                }, 'fast', 'easeInOutQuad', function () {
                    self.css({
                        left: '100%',
                        right: 'auto'
                    }).hide();

                    //clear content when finished,
                    // will help any handlers waiting for element to be removed
                    self.updateContent('');
                    if (callback) callback();
                });
            },
            slideOut: function () {
                this.css({
                    visibility: 'hidden',
                    display: 'block',
                    left: 'auto'
                });

                var width = this.outerWidth();
                $('.preview-scroll').stop().animate({
                    width: '45%'
                }, 500);

                this.css({
                    right: width * -1,
                    visibility: 'visible'
                }).stop().animate({
                    right: 0
                }, 500, 'easeInOutQuad');
            },
            updateContent: function (content) {
                this.find('.form').html(content);

                console.log(this.getBrowseHistory());
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
                $('.content-module-page-editor').trigger('previewdirty');
            }

        });

        $('.cms-form-editor .header .back').entwine({
            onclick: function (e) {
                e.preventDefault();

                $('.cms-form-editor').goBack();
            }
        });

        $('.cms-form-editor .header .close').entwine({
            onclick: function (e) {
                e.preventDefault();

                $('.cms-form-editor').hideForm();
            }
        });

        $('#content-module-page-editor-cms-form-editor .refresh').entwine({
            onclick: function (e) {
                e.preventDefault();

                $('#content-module-page-editor-cms-form-editor').refreshPreview();
            }
        });

        $('#content-module-page-editor-cms-form-editor form input[type=submit], #content-module-page-editor-cms-form-editor form button').entwine({
            onclick: function (e) {
                e.preventDefault();

                //append action so SS correctly picks it up
                var container = this.closest('#content-module-page-editor-cms-form-editor'),
                    form = this.closest('form'),
                    data = form.serialize() + '&' + this.attr('name') + '=' + '1',
                    self = this;

                this.addClass('loading');
                $.ajax({
                    url: form.attr('action'),
                    method: 'POST',
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
                            $('#content-module-page-editor-cms-form-editor').refreshPreview();
                        } else {
                            if (data.Message) {
                                statusMessage(data.Message, 'bad');
                            } else if (data) { //we have html
                                container.updateContent(data);
                                $('#content-module-page-editor-cms-form-editor').refreshPreview();
                            }

                            //handle redirects in the headers
                            var url = xhr.getResponseHeader('X-ControllerURL');

                            if (url) {
                                container.loadForm(url);
                            }
                        }
                    }
                });
            }
        });

        $('#content-module-page-editor-cms-form-editor .ss-gridfield .ss-gridfield-item').entwine({
            onclick: function (e) {
                e.preventDefault();
                e.stopImmediatePropagation();

                var editLink = this.find('.edit-link');
                if (editLink.length) {
                    this.closest('#content-module-page-editor-cms-form-editor').loadForm(editLink.attr('href'), true);
                }
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
                return $($('.cms-preview').find('iframe').get(0).contentDocument).find('.content-module-page-editor-module-handler');
            }
        });
    });

})(jQuery);