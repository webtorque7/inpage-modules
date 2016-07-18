(function($){
    $.entwine('ss', function($){
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
    });
})(jQuery);