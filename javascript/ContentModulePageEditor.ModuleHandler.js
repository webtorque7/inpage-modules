(function ($) {
    var SCROLL_POSITION_KEY = 'ContentModulePageEditor.ScrollPosition';

    $.entwine('ss.preview', function ($) {
        $('.content-module-page-editor-module-handler').entwine({
            onadd: function () {
                this._super();

                this.setup();
            },
            setup:function() {
                var moduleContainer = this.getModuleContainer(),
                    positioning = moduleContainer.css('position');

                //if default positioning, change to relative
                if (positioning === 'static') {
                    moduleContainer.css('position', 'relative');
                }

                moduleContainer.on('mouseover', this.enable.bind(this));
                moduleContainer.on('mouseout', this.disable.bind(this));
            },
            enable: function() {
                this.addClass('highlight');
            },
            disable: function() {
                this.removeClass('highlight');
            },
            getModuleContainer: function() {
                return this.parent();
            },
            onclick:function(e) {
                e.preventDefault();

                $('.content-module-page-editor-module-handler').removeClass('active');

                this.addClass('active');
                var data = JSON.stringify({
                    target: '.content-module-page-editor',
                    type: 'event',
                    event: 'editmodule',
                    data: {
                        ID: this.data('module-id')
                    }
                });

                window.parent.postMessage(data, '*');
            }
        });

        $(document).on('ready', function(e, data){
            console.log('triggering preview loaded');
            var data = JSON.stringify({
                target: '.module-edit-form',
                type: 'event',
                event: 'previewloaded',
                data: {
                    some: 'dummy data'
                }
            });

            window.parent.postMessage(data, '*');
        });

        $(window).on('scroll', function(){
            if (window.localStorage) {
                window.localStorage.setItem(SCROLL_POSITION_KEY + window.location.href, $(window).scrollTop());
            }
        });

        if (window.localStorage) {
            var oldScrollPosition = window.localStorage.getItem(SCROLL_POSITION_KEY + window.location.href);

            if (oldScrollPosition) {
                $('body,html').animate({scrollTop: oldScrollPosition}, 1000);
            }
        }
    });

})(jQuery);