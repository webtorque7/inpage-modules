(function ($) {
    //window.debug = true;

    /**
     * Gets the components of a url
     *
     * @param url
     */
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
        $('.visual-editor').entwine({
            onadd: function () {
                this.hideMenuPanel();
                this._super();
            },
            redraw:function() {
                this._super();

            },
            hideMenuPanel: function() {
                setTimeout(function () {
                    $('.cms-panel.cms-menu').togglePanel(false, false, true);
                }, 50);
            },
            onpreviewdirty: function () {
                this.getPreview().reload();
            },
            getPreview: function() {
                return this.find('.visual-editor-preview');
            },
            oneditmodule: function (e, data) {
                this.editModule(data.ID);
            },
            editModule: function (id) {
                var urlComponents = getURLComponents(this.data('edit-module-url')),
                    url =  urlComponents.url + '/' + id + urlComponents.query;

                $('.visual-editor-form').loadForm(url);
            }
        });
    });

})(jQuery);