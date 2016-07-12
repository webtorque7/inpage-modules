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
                formEditor = this.closest('.cms-form-editor');

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

                this.closest('.cms-form-editor').loadForm(this.attr('href'), true);
            }
        });

        /**
         * Add Button
         */
        $('.module-manager .add-button').entwine({
            onclick: function (e) {
                e.preventDefault();

                this.closest('.cms-form-editor').loadForm(this.attr('href'), true);
            }
        });

        /**
         * Cancel add
         */
        $('#Form_ModuleAddForm .cancel').entwine({
            onclick: function (e) {
                e.preventDefault();

                this.closest('.cms-form-editor').goBack();
            }
        });
    });
})(jQuery);