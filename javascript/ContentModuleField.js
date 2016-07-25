/**
 * @todo Prevent form being marked as changed to prevent alert
 */
(function ($) {

    function getURLComponents(url) {
        var urlComponent = url,
            queryComponent = '';

        if (urlComponent.indexOf('?') !== -1) {
            var parts = urlComponent.split('?');
            urlComponent = parts[0];
            queryComponent = '?' + parts[1];
        }

        return {
            url: urlComponent,
            query: queryComponent
        };
    }

    $.entwine('ss', function ($) {

        $('.content-module-field *').entwine({
            getContentModuleField: function () {
                return this.closest('.content-module-field');
            }
        });

        $('.content-module-field').entwine({
            PreventAccordion: false,

            onadd: function () {
                var self = this;
                this._super();
            },
            onremove: function () {
                this._super();
            },
            /*fromTabSet: {
             ontabsshow: function() {
             this.find('.content-module').accordion("resize");
             }
             },*/
            getPageID: function () {
                var id = $('#Form_EditForm_ID').val();
                if (!id) {
                    var pathname = window.location.pathname.match(/\d+/)
                    id = pathname[0];
                }
                return id;
            },

            getScrollContainer: function () {
                return this.closest('.tab');
            },
            scrollToEnd: function () {
                var container = this.getScrollContainer();

                if (container.scrollHeight > container.height()) {
                    container.scrollTop(container.scrollHeight - container.height());
                }
            },

            //modules
            getModulesContainer: function () {
                return this.find('.current-modules .modules');
            },
            addModule: function (module) {
                if (this.getModulesContainer().length) {
                    this.getModulesContainer().append(module).reloadAccordion();
                }
                else {
                    this.find('.current-modules p').hide();
                    this.find('.current-modules .message').show();
                    this.find('.current-modules').append('<div class="modules ui-accordion ui-widget ui-helper-reset ui-sortable" role="tablist">' + module + '</div>').reloadAccordion();
                }
                this.scrollToEnd();
            },
            sortModules: function (e, ui) {
                var contentModuleField = this;

                var modules = {'Sort': {}};

                $('.content-module').each(function (index) {
                    modules.Sort[$(this).data('id')] = index;
                });

                var sortURL = getURLComponents(contentModuleField.getSortURL());
                if (modules) {
                    contentModuleField.showLoading();
                    $.post(
                        sortURL.url + '/' + contentModuleField.getPageID() + sortURL.query,
                        modules,
                        function (data) {
                            contentModuleField.hideLoading();

                            //allow accordion to open again
                            contentModuleField.setPreventAccordion(false);

                            if (data.Message) {
                                statusMessage(data.Message, data.Status ? 'good' : 'bad');
                            }

                            contentModuleField.reloadPreview();
                        });
                }
            },

            //add fields
            hideAddFields: function () {
                this.getAddFields().hide();
            },
            showAddFields: function () {
                this.getAddFields().show();
            },
            getAddFields: function () {
                return this.find('.add-fields');
            },

            //exiting fields
            hideExistingFields: function () {
                this.getExistingFields().hide();
            },
            showExistingFields: function () {
                this.getExistingFields().show();
            },
            getExistingFields: function () {
                return this.find('.existing');
            },

            //urls
            getURL: function () {
                return this.data('url');
            },
            getExistingURL: function () {
                return this.data('existing_url');
            },
            getAddNewURL: function () {
                return this.data('add_new_url');
            },
            getAddExistingURL: function () {
                return this.data('add_existing_url');
            },
            getCopyURL: function () {
                return this.data('copy_url');
            },
            getSortURL: function () {
                return this.data('sort_url');
            },
            getModuleURL: function () {
                return this.data('module_url');
            },

            //loading
            showLoading: function () {
                this.closest('.cms-content').addClass('loading');
            },
            hideLoading: function () {
                this.closest('.cms-content').removeClass('loading');
            },
            reloadPreview: function () {
                $('.cms-container .cms-edit-form').trigger('aftersubmitform');
            }

        });

        $('.content-module-field .current-modules .modules').entwine({
            onadd: function () {
                var self = this;
                this.setupAccordion();
                this._super();
            },
            reloadAccordion: function () {
                this.accordion('destroy')
                    .setupAccordion();
                return this;
            },
            setupAccordion: function () {
                var self = this;

                this
                    .accordion({
                        header: '> div > h4',
                        collapsible: true,
                        active: false,
                        heightStyle: 'content',
                        activate: function (e, ui) {
                            if (ui.newPanel.length) {
                                $(ui.newPanel).closest('.content-module').loadActions();
                            }
                            else {
                                self.getContentModuleField().find('.content-module-field-actions').fadeOut();
                            }
                        }
                    })
                    .sortable({
                        opacity: 0.6,
                        update: function (e, ui) {
                            self.closest('.content-module-field').sortModules(e, ui);
                        },
                        //placeholder: 'ui-state-highlight',
                        forcePlaceholderSize: true
                    });
                return this;
            },
            onremove: function () {
                this
                    .accordion('destroy')
                    .sortable('destroy');
                this._super();

            }
        });

        $('.content-module-type-dropdown').entwine({
            onchange: function () {
                var index = this.prop('selectedIndex');

                if (index == 0) {
                    this.getContentModuleField().hideAddFields();
                }
                else {
                    this.getContentModuleField().showAddFields();
                    this.loadExisting();
                }
                //prevent SS from marking as changed
                return false;
            },

            //existing modules
            loadExisting: function () {
                var self = this;
                var contentModuleField = self.getContentModuleField();

                contentModuleField.showLoading();

                var url = getURLComponents(contentModuleField.getExistingURL());

                $.get(url.url + '/' + this.val() + url.query, function (data) {
                    contentModuleField.hideLoading();
                    if (data.Status) {
                        contentModuleField.find('.content-module-existing-dropdown').updateModules(data.Modules);
                        contentModuleField.showExistingFields();
                    }
                    else {
                        contentModuleField.hideExistingFields();
                        //statusMessage(data.Message, 'bad');
                    }
                });
            },
            setupExistingModules: function (modules) {

            },
            reset: function () {
                this.val('');
                this.trigger('liszt:updated');
            }


        });

        $('.content-module-existing-dropdown').entwine({
            EmptyText: 'Select an existing module',

            updateModules: function (modules) {
                var self = this;
                this.insertEmpty();
                if (modules && modules.length) {
                    this.html('');

                    $(modules).each(function () {
                        self.append($('<option></option>').attr('value', this.ID).html(this.Title));
                    });

                    this.trigger('liszt:updated');
                }
            },
            insertEmpty: function () {
                this.prepend($('<option></option>').attr('value', '').html(this.getEmptyText()));
            }
        });

        $('.content-module-add-new').entwine({
            onclick: function (e) {
                e.preventDefault();

                var contentModuleField = this.getContentModuleField();

                var module = contentModuleField.find('.content-module-type-dropdown').val();

                if (module) {
                    var url = getURLComponents(contentModuleField.getAddNewURL());
                    url = url.url + '/' + module + '/' + contentModuleField.getPageID() + url.query;

                    contentModuleField.showLoading();
                    $.get(url, function (data) {
                        contentModuleField.hideLoading();
                        if (data.Status) {
                            if (data.Message) {
                                statusMessage(data.Message, 'good');
                            }

                            if (data.Content) {
                                contentModuleField.addModule(data.Content);
                            }
                        }
                        else {
                            statusMessage(data.Message, 'bad');
                        }
                        contentModuleField.hideAddFields();
                        contentModuleField.find('.content-module-type-dropdown').reset();
                    });
                }
                else {
                    statusMessage('Please select a module type', 'bad');
                }

            }
        });

        $('.content-module-add-existing').entwine({
            onclick: function (e) {
                e.preventDefault();

                var contentModuleField = this.getContentModuleField();

                var module = contentModuleField.find('.content-module-existing-dropdown').val();

                if (module) {
                    var url = getURLComponents(contentModuleField.getAddExistingURL());
                    url = url.url + '/' + module + '/' + contentModuleField.getPageID() + url.query;

                    contentModuleField.showLoading();

                    $.get(url, function (data) {
                        contentModuleField.hideLoading();
                        if (data.Content) {
                            contentModuleField.addModule(data.Content);
                        }
                        if (data.Status) {
                            if (data.Message) {
                                statusMessage(data.Message, 'good');
                            }
                        }
                        else {
                            contentModuleField.hideLoading();
                            statusMessage(data.Message, 'bad');
                        }
                        contentModuleField.hideAddFields();
                        $('#ContentModule_ModuleType').reset();
                    });
                }
                else {
                    statusMessage('Please select an existing module', 'bad');
                }

            }
        });

        $('.content-module-copy').entwine({
            onclick: function (e) {
                e.preventDefault();

                var contentModuleField = this.getContentModuleField();

                var module = contentModuleField.find('.content-module-existing-dropdown').val();

                if (module) {
                    var url = getURLComponents(contentModuleField.getCopyURL());
                    url = url.url + '/' + module + '/' + contentModuleField.getPageID() + url.query;

                    contentModuleField.showLoading();

                    $.get(url, function (data) {
                        contentModuleField.hideLoading();
                        if (data.Content) {
                            contentModuleField.addModule(data.Content);
                        }
                        if (data.Status) {
                            if (data.Message) {
                                statusMessage(data.Message, 'good');
                            }
                        }
                        else {
                            contentModuleField.hideLoading();
                            statusMessage(data.Message, 'bad');
                        }
                        contentModuleField.hideAddFields();
                        $('#ContentModule_ModuleType').reset();
                        contentModuleField.reloadPreview();
                    });
                }
                else {
                    statusMessage('Please select an existing module', 'bad');
                }

            }
        });

        $('.content-module').entwine({
            onadd: function () {
                if (this.data('fix-tab-size')) {
                    this.fixTabSize();
                }
            },
            fixTabSize: function () {
                if (this.find('ul.ui-tabs-nav li').length > 1) {
                    var form = this.find('.form'),
                        tabs = this.find('div.tab'),
                        formVisible = form.is(':visible');

                    if (!formVisible) form.show().css('visibility', 'hidden');

                    var maxHeight = 0;
                    tabs.each(function () {
                        var tab = $(this),
                            visible = tab.is(':visible');

                        if (!visible) tab.show().css('visibility', 'hidden');
                        maxHeight = Math.max(maxHeight, tab.height());
                        if (!visible) tab.hide().css('visibility', 'visible');
                    });


                    tabs.height(maxHeight);

                    if (!formVisible) form.hide().css('visibility', 'visible');
                }
            },
            submitModule: function (action, callback) {
                var self = this,
                    contentModuleField = self.getContentModuleField(),
                    moduleURL = getURLComponents(contentModuleField.getModuleURL());

                var url = moduleURL.url + '/' + action + '/' + this.getID() + moduleURL.query;

                contentModuleField.showLoading();

                var fields = this.getFields().serializeArray();
                fields.push({name: 'PageID', value: contentModuleField.getPageID()});

                $.post(url, fields, function (data) {
                    contentModuleField.hideLoading();

                    if (data.Status) {
                        if (data.Message) {
                            statusMessage(data.Message, 'good');
                        }

                        if (data.Content) {
                            self.updateFromServer(data.Content);
                        }
                        $(window).trigger('resize');

                        if (callback && typeof(callback) == 'function') {
                            callback();
                        }
                    }
                    else {
                        if (data.Message) {
                            statusMessage(data.Message, 'bad');
                        }
                    }


                });
            },
            reloadModule: function () {
                var self = this;
                url = getURLComponents(self.getContentModuleField().getURL());

                url = url.url + '/reload/' + this.getID() + url.query;

                $.get(url, function (data) {
                    if (data.Status) {
                        self.updateFromServer(data.Content);
                    }
                });
            },
            updateTinyMCE: function () {
                this.getContentModuleField().find('textarea').each(function () {
                    var tEditor = tinymce.get($(this).attr('id'));
                    if (tEditor) tEditor.save();
                });

            },
            getFields: function () {
                //update any tinymce fields
                if (tinymce) this.updateTinyMCE();

                return this.find('.field input, .field select, .field textarea');
            },
            getID: function () {
                return this.data('id');
            },
            updateFromServer: function (content) {
                var $content = $(content);

                //todo - only update text to prevent having to insert accordian stuff
                var title = $content.find('h4').html();
                if (title) this.find('h4').html(title).prepend('<span class="ui-accordion-header-icon ui-icon ui-icon-triangle-1-e"></span>');

                var form = $content.find('.form').html();
                if (form) this.find('.form').html(form);

                this.loadActions();
                this.resize();

                //check for changed elements, reset if no longer are any
                var form = this.closest('form');

                if (form.hasClass('changed') && form.find('.changed').length === 0) {
                    form.removeClass('changed');
                }

                this.getContentModuleField().reloadPreview();
            },
            resize: function () {
                var form = this.find('form');

                var currentHeight = form.outerHeight();

                if (form.scrollHeight > currentHeight) {
                    form.height(form.scrollHeight);
                }

                this.closest('.modules').accordion('resize');
            },
            onwindowresize: function () {
                this.loadActions();
            },
            loadActions: function () {
                var cmsActions = this.closest('form').find('.cms-content-actions'),
                    buttonsHtml = this.find('.Actions').html(),
                    moduleFieldActions = this.getContentModuleField().find('.content-module-field-actions'),
                    moduleFieldActionsContainer = moduleFieldActions.find('.Actions');

                moduleFieldActions.css({
                    left: cmsActions.offset().left,
                    bottom: cmsActions.outerHeight()
                }).fadeIn();


                moduleFieldActionsContainer.css({
                    width: cmsActions.width()
                }).html('');

                //strip button html if needed
                var insertHTML = $('<div>' + buttonsHtml + '</div>');
                insertHTML.find('button, input').each(function () {
                    var self = $(this);
                    console.log('fixing', this);
                    if (this.tagName.toLowerCase() === 'button') {
                        var buttonText = self.find('.ui-button-text');
                        if (buttonText.length) {
                            self.html(buttonText.html());
                        }
                    }
                });

                moduleFieldActionsContainer.html(insertHTML.html());
            }
        });

        /**
         * prevent creating more editors when module is sorted (this triggers onadd)
         */
        $('.content-module textarea.htmleditor').entwine({
            onadd: function () {
                if (!this.data('tinymce-added')) {
                    this.data('tinymce-added', true);
                    this._super();
                } else {
                    //remove any other tinymce elements, triggered by drag and drop sorting
                    this.siblings().remove();

                    //remove any dropdown lists
                    $('.mceListBoxMenu[id*=' + this.attr('id') + ']').remove();
                    this._super();
                }
            }
        });

        //global controls for active module, triggers action on active module
        var globalButtons = 'body .content-module-field .content-module-field-actions .Actions button, ' +
            'body .content-module-field .content-module-field-actions .Actions input[type=submit]';

        $(globalButtons).entwine({
            onclick: function (e) {
                e.preventDefault();

                //active module
                var open = this.getContentModuleField().find('.ui-accordion-content-active'),
                    activeModule = open.closest('.content-module');

                this.addClass('loading');
                console.log(activeModule.find('[name="' + this.attr('name') + '"]'));
                //trigger click on real button
                activeModule.find('[name="' + this.attr('name') + '"]').trigger('click');
            }
        });

        var publishSaveButtons = 'body .content-module-field .content-module .Actions input[type=submit].publish, ' +
            'body .content-module-field .content-module .Actions input[type=submit].save, '+
            'body .content-module-field .content-module .Actions button.publish, ' +
            'body .content-module-field .content-module .Actions button.save';

        $(publishSaveButtons).entwine({
            onclick: function (e) {
                e.preventDefault();
                e.stopPropagation();

                var name = this.attr('name');
                var action = name.substring(name.indexOf('_') + 1, name.lastIndexOf('_'));

                this.closest('.content-module').submitModule(action);
                return false;
            }
        });

        var deleteButtons = 'body .content-module-field .content-module input.unlink[type=submit], ' +
            'body .content-module-field .content-module input.delete[type=submit], ' +
            'body .content-module-field .content-module input.unpublish[type=submit], ' +
            'body .content-module-field .content-module button.unlink, ' +
            'body .content-module-field .content-module button.delete, ' +
            'body .content-module-field .content-module button.unpublish';

        $(deleteButtons).entwine({
            onclick: function (e) {
                e.preventDefault();
                e.stopPropagation();

                var name = this.attr('name');
                var action = name.substring(name.indexOf('_') + 1, name.lastIndexOf('_'));

                var contentModule = this.closest('.content-module');

                contentModule.submitModule(action, function () {
                    if (action == 'unpublish') {
                        contentModule.reload();
                    }
                    else {
                        contentModule.remove();
                    }
                });

                return false;
            }
        });

        //make sure first tab is active so accordian fully expands
        $('.content-module-field .content-module > h4').entwine({
            onclick: function () {
                var tabset = this.parent().find('.ss-tabset');

                if (tabset.length) {
                    //check for open tab
                    var panelVisible = false;
                    tabset.find('.ui-tabs-panel').each(function () {
                        if ($(this).is(':visible')) {
                            panelVisible = true;
                            return false;
                        }
                    });

                    if (!panelVisible) {
                        tabset.find('.ui-tabs-panel').first().show();
                    }
                }
            }
        });
    });
})(jQuery);