(function ($) {
    $.entwine('ss', function ($) {

        /**
         * Module Sorter
         */
        $('.module-manager .module-sorter').entwine({
            onadd: function () {
                var self = this;

                this.sortable({
                    opacity: 0.6,
                    update: function (e, ui) {
                        self.sortModules(e, ui);
                    },
                    forcePlaceholderSize: true
                });
            },
            sortModules: function (e, ui) {
                var self = this,
                    modules = {'Sort': {}};
                formEditor = this.closest('.visual-editor-form');

                this.find('.module').each(function (index) {
                    modules.Sort[$(this).data('id')] = index;
                });

                var sortURL = this.data('sort-url');

                if (modules) {
                    formEditor.addClass('loading');

                    $.post(
                        sortURL,
                        modules,
                        function (data) {
                            formEditor.removeClass('loading');

                            if (data.Message) {
                                statusMessage(data.Message, data.Status ? 'good' : 'bad');
                            }

                            formEditor.refreshPreview();
                        });
                }
            }
        });

        /**
         * Module actions
         */
        $('.module-manager .module .links a').entwine({
            onclick: function (e) {
                e.preventDefault();

                this.closest('.visual-editor-form').loadForm(this.attr('href'), true);
            }
        });

        /**
         * Add Button
         */
        $('.module-manager .add-button').entwine({
            onclick: function (e) {
                e.preventDefault();

                this.closest('.visual-editor-form').loadForm(this.attr('href'), true);
            }
        });

        /**
         * Cancel add
         */
        $('#Form_ModuleAddForm .cancel, #Form_ModuleAddExistingForm .cancel').entwine({
            onclick: function (e) {
                e.preventDefault();

                this.closest('.cms-form-editor').goBack();
            }
        });


        /**
         * Add existing modules
         *-----------------------------------------------*/

        /**
         * Select module type screen
         */
        $('.module-types').entwine({
            modulesURL: function () {
                return this.data('existing-modules-url')
            }
        });

        $('.module-types .module-type').entwine({
            onclick: function (e) {
                e.preventDefault();
                this.loadExistingModules();
            },

            /**
             * Retrieve existing modules from server
             */
            loadExistingModules: function () {
                var url = this.closest('.module-types').modulesURL(),
                    self = this;

                this.closeOtherLists();

                $.get(url.replace('{type}', this.data('type')), function (response) {
                    if (response.Status) {
                        self.showExistingModules(response.Data);
                    } else {
                        statusMessage(response.Message, 'bad');
                    }

                });
            },

            /**
             * Adds list of of existing modules
             *
             * modules takes the form:
             *
             * <code>
             * {
             *     ID: 1
             *     Title: 'My Module'
             *     LastEdited: '2016-09-01 10:00'
             * }
             * </code>
             *
             * @param modules
             */
            showExistingModules: function (modules) {
                var modulesContainer = this.find('.existing-modules');
                list = $('<ul></ul>');

                if (modules && modules.length) {
                    $.each(modules, function () {
                        var li = '<li data-id="' + this.ID + '">' + this.Title + ' - ' + this.LastEdited + '</li>';
                        list.append(li)
                    });
                }

                modulesContainer.html(list).addClass('open');
            },

            /**
             * Closes any open module lists
             */
            closeOtherLists: function () {
                $('.module-type .existing-modules').removeClass('open');
            },

            /**
             * Shows/hides this module based on matching the keyword
             *
             * @param keyword
             */
            filter: function (keyword) {
                var title = this.find('h4').text();

                if (!keyword || keyword.length === 0) {
                    this.show();
                } else if (title.toLowerCase().indexOf(keyword.toLowerCase()) !== -1) {
                    this.show();
                } else {
                    this.hide();
                }
            }
        });

        $('.module-types .module-type .existing-modules li').entwine({
            onclick: function (e) {
                e.preventDefault();
                e.stopImmediatePropagation();

                this.createModule();
            },
            createModule:function() {
                var url = this.getUrl();

                $.post(url, {ID: this.data('id')}, function(data){
                    if (data.Status && data.RedirectLink) {
                        $('.visual-editor-form').loadForm(data.RedirectLink);
                        if (data.Message) {
                            statusMessage(data.Message, 'good');
                        }

                    } else {
                        statusMessage(data.Message, 'bad');
                    }
                });
            },
            getUrl:function() {
                return this.closest('.existing-modules').data('create-existing-url');
            }

        });

        $('.module-types .search input').entwine({
            onsearch: function () {
                //close any open lists
                $('.module-type .existing-modules').removeClass('open');

                $('.module-types .module-type').filter(this.val());
            }
        });

    });
})(jQuery);