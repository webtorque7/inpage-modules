/**
 * @todo Prevent form being marked as changed to prevent alert
 */
(function ($) {

        $.entwine('ss', function ($) {

		$('.content-module-field *').entwine({
			getContentModuleField:function() {
				return this.closest('.content-module-field');
			}
		});

                $('.content-module-field').entwine({
                        PreventAccordion: false,

                        onadd: function () {
				var self = this;
                                //sorting
                                $(this).find('.current-modules').sortable({
                                        opacity: 0.6,
                                        handle: '.handle',
                                        update: function (e, ui) {
                                                self.sortModules(e, ui)
                                        },
                                        placeholder: 'ui-state-highlight',
                                        forcePlaceholderSize: true,
                                        start: function (e, ui) {
                                                self.setPreventAccordion(true);
                                        },
                                        deactivate: function(e,ui) {
                                                //todo: make this work
                                                //if it hasn't moved, re-enable accordion
                                                if (ui.position.left == ui.originalPosition.left && ui.position.top == ui.originalPosition.top) {
                                                        self.setPreventAccordion(false);
                                                }
                                        }
                                });

                                this._super();
                        },
                        onremove: function () {
                                this._super();
                        },
                        getPageID: function () {
                                return $('#Form_EditForm_ID').val();
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
                                return this.find('.current-modules');
                        },
                        addModule: function (module) {
                                this.getModulesContainer().append(module);
                                this.scrollToEnd();
                        },
                        sortModules: function (e, ui) {
				var contentModuleField = this;

                                var modules = {'Sort': {}};

                                $('.content-module').each(function (index) {
                                        modules.Sort[$(this).data('id')] = index;
                                });

                                if (modules) {
                                        contentModuleField.showLoading();
                                        $.post(contentModuleField.getSortURL() + '/' + contentModuleField.getPageID(), modules, function (data) {
                                                contentModuleField.hideLoading();

                                                //allow accordion to open again
                                                contentModuleField.setPreventAccordion(false);

                                                if (data.Message) {
                                                        statusMessage(data.Message, data.Status ? 'good' : 'bad');
                                                }
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
                        getURL: function() {
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
                        }

                });

                //prevent change tracking
                $('.content-module-field input, .content-module-field select, .content-module-field textarea').entwine({
                        onadd:function() {
                                this.addClass('no-change-track');
                                this._super();
                        },
                        onunmatch:function() {
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

                                $.get(contentModuleField.getExistingURL() + '/' + this.val(), function (data) {
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
                        reset:function() {
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
                                        var url = contentModuleField.getAddNewURL() + '/' + module + '/' + contentModuleField.getPageID();
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
                                        var url = contentModuleField.getAddExistingURL() + '/' + module + '/' + contentModuleField.getPageID();
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

                $('.content-module').entwine({
                        submitModule: function (action, callback) {
                                var self = this;
				var contentModuleField = self.getContentModuleField();

                                var url = contentModuleField.getModuleURL() + '/' + action + '/' + this.getID();

                                contentModuleField.showLoading();

                                var fields = this.getFields().serializeArray();
                                fields.push({name:'PageID', value:contentModuleField.getPageID()});

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

                                var url = self.getContentModuleField().getURL() + '/reload/' + this.getID();

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

                                this.resize();
                        },
                        resize: function () {
                                var form = this.find('form');

                                var currentHeight = form.outerHeight();

                                if (form.scrollHeight > currentHeight) {
                                        form.height(form.scrollHeight);
                                }

                                if (this.hasClass('ui-accordion'))
                                        this.accordion('resize');
                        },
                        //accordion
                        onaccordionbeforeactivate: function (e, ui) {
                                if (this.getContentModuleField().getPreventAccordion()) {
                                        return false;
                                }
                        }

                });

                $('body .content-module-field .content-module .Actions input[type=submit].publish, body .content-module-field .content-module .Actions input[type=submit].save').entwine({
                        onclick: function (e) {
                                e.preventDefault();
                                e.stopPropagation();

                                var name = this.attr('name');
				var action = name.substring(name.indexOf('_') + 1, name.lastIndexOf('_'));

                                this.closest('.content-module').submitModule(action);
                                return false;
                        }
                });

                $('body .content-module-field .content-module input.unlink[type=submit],body .content-module-field .content-module input.delete[type=submit]').entwine({
                        onclick: function (e) {
                                e.preventDefault();
                                e.stopPropagation();

                                var name = this.attr('name');
				var action = name.substring(name.indexOf('_') + 1, name.lastIndexOf('_'));

                                var contentModule = this.closest('.content-module');

                                contentModule.submitModule(action, function(){
                                        contentModule.remove();
                                });

                                return false;
                        }
                });
        });
})(jQuery);