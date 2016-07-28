(function($){
    $.entwine('ss', function($){
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

        $('.visual-editor-toolbox').entwine({
            StartX: null,
            StartY: null,
            StartMouseX: null,
            StartMouseY: null,

            ondragstart: function(e) {
                this.setStartMouseX(e.originalEvent.clientX);
                this.setStartMouseY(e.originalEvent.clientY);
                this.setStartX(this.offset().left);
                this.setStartY(this.offset().top);
            },
            ondrag:function(e) {
                var mouseX = e.originalEvent.clientX,
                    mouseY = e.originalEvent.clientY,
                    newPositionX = this.getStartX() + (mouseX - this.getStartMouseX()),
                    newPositionY = this.getStartY() + (mouseY - this.getStartMouseY());

                //keep within preview window
                newPositionX = this.boundsX(newPositionX);
                newPositionY = this.boundsY(newPositionY);

                //last position is negative for some strange reason
                if (mouseX > 0 && mouseY > 0) {
                    this.css({
                        left: newPositionX,
                        top: newPositionY
                    });
                }
            },
            boundsX:function(x) {
                var container = $('.visual-editor-preview'),
                    leftBounds = container.offset().left,
                    rightBounds = leftBounds + container.outerWidth() - this.outerWidth();

                if (x < leftBounds) return leftBounds;
                if (x > rightBounds) return rightBounds;

                return x;
            },
            boundsY:function(y) {
                var container = $('.visual-editor-preview'),
                    topBounds = container.offset().top,
                    bottomBounds = topBounds + container.outerHeight() - this.outerHeight();

                if (y < topBounds) return topBounds;
                if (y > bottomBounds) return bottomBounds;

                return y;
            },
            onpreviewresized:function() {
                var x = this.offset().left,
                    y = this.offset().top;

                x = this.boundsX(x);
                y = this.boundsY(y);

                this.stop().animate({
                    left: x,
                    top: y
                }, 100);
            }
        });

        $('.visual-editor-toolbox a').entwine({
            onclick:function(e) {
                e.preventDefault();
                $('.visual-editor-form').loadForm(this.attr('href'));
            }
        });

        //stop default click running
        $('.visual-editor-toolbox .site-tree-form a').entwine({
            onclick: function(e) {
                e.preventDefault();
                e.stopImmediatePropagation();
            }
        });

        $('.visual-editor-toolbox .site-tree-form .field input[name=SiteTreeID]').entwine({
            onchange:function(e) {
                var id = this.val(),
                    urlComponents = getURLComponents($('.visual-editor-toolbox').data('page-url'));

                $('.cms-container').loadPanel(
                    urlComponents.url + '/' + id + urlComponents.query,
                    false,
                    'Content'
                );
            }
        });
    });
})(jQuery);