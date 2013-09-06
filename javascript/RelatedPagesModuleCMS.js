/**
 * @todo Prevent form being marked as changed to prevent alert
 */
(function($){

        $.entwine('ss', function($){

                $('input[type=checkbox].use-children').entwine({
                        onmatch:function() {
                                var self = this;
                                setTimeout(function(){
                                        self.toggleFields(true);
                                }, 100);

                                this._super();
                        },
                        onunmatch:function() {
                                this._super();
                        },
                        onchange:function() {
                                this.toggleFields();
                        },
                        /**
                         *
                         * @param loading Used when loading to automatically hide/show, no effect
                         */
                        toggleFields:function(loading) {
                                var cModule = this.closest('.content-module');
                                if (this.is(':checked')) {
                                        if (loading) {
                                                cModule.find('.non-category').hide();
                                                cModule.find('div.show-search').show();
                                        }
                                        else {
                                                cModule.find('.non-category').slideUp('slow', function(){
                                                        cModule.find('div.show-search').slideDown(function(){
                                                                cModule.resize();
                                                        }).trigger('change');
                                                });
                                        }
                                }
                                else {
                                        if (loading) {
                                                cModule.find('.non-category').show();
                                                cModule.find('div.show-search').hide();
                                        }
                                        else {
                                                cModule.find('.category').slideUp('slow', function(){
                                                        cModule.find('.non-category').slideDown(function(){
                                                                cModule.resize();
                                                        });
                                                });
                                        }

                                }
                        }

                });

                $('input[type=checkbox].show-search').entwine({
                        onmatch:function() {
                                this.toggleFields(true);

                                this._super();
                        },
                        onunmatch:function() {
                                this._super();
                        },
                        onchange:function() {
                                this.toggleFields();
                        },
                        toggleFields:function(loading) {
                                var cModule = this.closest('.content-module');
                                if (this.is(':checked')) {
                                        if (loading) {
                                                cModule.find('div.has-categories').show();
                                        }
                                        else {
                                                cModule.find('div.has-categories').slideDown().trigger('change');
                                        }

                                }
                                else {
                                        if (loading) {
                                                cModule.find('div.has-categories').hide();
                                                cModule.find('div.show-categories').hide();
                                        }
                                        else {
                                                cModule.find('div.has-categories').slideUp();
                                                cModule.find('div.show-categories').slideUp();
                                        }

                                }
                        }

                });

                $('input[type=checkbox].has-categories').entwine({
                        onmatch:function() {
                                this.toggleFields(true);

                                this._super();
                        },
                        onunmatch:function() {
                                this._super();
                        },
                        onchange:function() {
                                this.toggleFields();
                        },
                        toggleFields:function(loading) {
                                var cModule = this.closest('.content-module');
                                if (this.is(':checked')) {
                                        if (loading) {
                                                cModule.find('div.show-categories').show();
                                        }
                                        else {
                                                cModule.find('div.show-categories').slideDown();
                                        }

                                }
                                else {
                                        if (loading) {
                                                cModule.find('div.show-categories').hide();
                                        }
                                        else {
                                                cModule.find('div.show-categories').slideUp();
                                        }

                                }
                        }

                });

                var lastValue = 0;
               // var preventSubmission = false;

                $('.RelatedPagesModule .contentmoduletreedropdown input').entwine({
                        onchange:function() {

                               /* if (preventSubmission) {
                                        preventSubmission = false;
                                        return;
                                }*/

                                if (this.val() && this.val() != lastValue) {
                                        lastValue = this.val();
                                        var cModule = this.closest('.content-module');
                                        cModule.submitModule('save');
                                }

                        }
                });

               /* $('.RelatedPagesModule .TreeDropdownField').entwine({
                        onmatch:function() {
                                this._super();

                                this.setValue(0);
                        }
                });*/

               /* $('.RelatedPagesModule .contentmoduletreedropdown ins').entwine({
                        onclick:function(e) {
                                e.preventDefault();

                                preventSubmission = true;
                                this._super();
                        }
                });*/


        });
})(jQuery);