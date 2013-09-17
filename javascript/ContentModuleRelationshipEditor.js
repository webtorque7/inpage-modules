(function ($) {
        $.entwine('ss', function ($) {

                $('.ContentModuleRelationshipEditor').entwine({
                        onadd:function() {
                                var self = this;

                                this._super();
                        },
                        getSortURL:function() {
                                return this.data('sort_url');
                        },
                        sortFields:function() {
                                var self = this;
                                var items = {'Sort': {}};

                                self.find('tbody tr').each(function (index) {
                                        items.Sort[$(this).data('id')] = index;
                                });

                                if (items) {
                                        this.closest('.cms-content').addClass('loading');
                                        $.post(self.getSortURL(), items, function (data) {
                                                self.closest('.cms-content').removeClass('loading');

                                                if (data.Message) {
                                                        statusMessage(data.Message, data.Status ? 'good' : 'bad');
                                                }
                                        });
                                }
                        },
			getReloadURL:function() {
				return this.data('reload_url');
			},
			reload:function() {
				var self = this;

				self.closest('.cms-content').addClass('loading');
				$.ajax({
					url: self.getReloadURL(),
					type: 'GET',
					complete: function () {
						self.closest('.cms-content').removeClass('loading');
					},
					success: function (data, status, xhr) {

						if (data.Status) {
							if (data.Message) {
								statusMessage(data.Message, 'good');
							}

							if (data.Content) {
								self.html($(data.Content).html());
							}
							if (self.closest('.content-module').length) {
								self.closest('.content-module').resize();
							}
						}
						else if (data.Message) {
							statusMessage(data.Message, 'bad');
						}

					}
				});
			}

                });

		$('.ContentModuleRelationshipEditor table tbody').entwine({
			onadd:function() {
				var self = this;
				//make table rows sortable
				this.sortable({
					handle: '.cmre-handle',
					helper:function(e, ui) {
						ui.children().each(function() {
							$(this).width($(this).width());
						});
						return ui;
					},
					update: function (e, ui) {
						self.getEditor().sortFields(e, ui);
					}
				}).disableSelection();
			},
			getEditor:function(){
				return this.closest('.ContentModuleRelationshipEditor');
			},
			onremove:function(){
				this._super();
			}
		});

                $('.ContentModuleRelationshipEditor button.remove-link').entwine({
			onclick: function (e) {
				e.preventDefault();

				var self = this;

				this.closest('.cms-content').addClass('loading');
				$.get(this.data('url'), function (data) {
					self.closest('.cms-content').removeClass('loading');
					if (data.Status) {
						statusMessage(data.Message, 'good');
						if (data.Content) {
							//only update the relationshipeditor
							self.closest('.ContentModuleRelationshipEditor').html($(data.Content).html());
						}
					}
					else statusMessage(data.Message, 'bad');
				});
			}

		});

		$('.ContentModuleRelationshipEditor button.delete-link').entwine({
			onclick: function (e) {
				e.preventDefault();

				var self = this;

				if(!confirm(ss.i18n._t('TABLEFIELD.DELETECONFIRMMESSAGE'))) {

					return false;
				} else {
					this.doDelete();
				}

			},
			doDelete:function() {
				var self = this;
				this.closest('.cms-content').addClass('loading');
				$.get(this.data('url'), function (data) {
					self.closest('.cms-content').removeClass('loading');
					if (data.Status) {
						statusMessage(data.Message, 'good');
						if (data.Content) {
							//only update the relationshipeditor
							self.closest('.ContentModuleRelationshipEditor').html($(data.Content).html());
						}
					}
					else statusMessage(data.Message, 'bad');
				});
			}
		});

                $('.contentmoduleupload .ss-uploadfield-files li').entwine({
                        onadd: function () {
                                var id = this.data('fileid');

                                if (id) {
                                        //this.closest('.content-module').reload();
                                }
                                this._super();
                        },
                        onremove: function () {

                        }
                });

                var currentAction = null;
		var currentEditor = null;
                var dialog = null;

                //add new
                $('.ContentModuleRelationshipEditor a.action-new, .ContentModuleRelationshipEditor a.edit-item').entwine({
                        onclick: function (e) {
                                var self = this;
                                e.preventDefault();

                                var url = this.data('url');
                                //link instead of button
                                if (!url) url = this.attr('href');

                                currentAction = this;
				currentEditor = this.closest('.ContentModuleRelationshipEditor');

                                this.closest('.cms-content').addClass('loading');
                                $.ajax({
                                        url:url,
                                        complete:function() {
                                                self.closest('.cms-content').removeClass('loading');
                                        },
                                        success:function (data) {

                                                if (data.Status) {
                                                        dialog = $(data.Content).dialog({
                                                                width: 762,
								close: function(){
									//make sure it is removed so no conflicting ids
									$(this).dialog('destroy').remove();
								}
                                                        });
                                                }
                                                else if (data.Message) {
                                                        statusMessage(data.Message, 'bad');
                                                }

                                        }
                                });
                        }
                });

                $('.ContentModuleRelationshipEditor tr').entwine({
                        onclick:function(e) {
                                if (e.target.tagName.toLowerCase() != 'a' && e.target.tagName.toLowerCase() != 'button') {
                                        this.find('a.edit-item').click();
                                }
                        }
                });

                //add existing
                $('.ContentModuleRelationshipEditor a.action-existing').entwine({
                        onclick: function (e) {
                                var self = this;
                                e.preventDefault();

                                var url = this.data('url');
                                //link instead of button
                                if (!url) url = this.attr('href');

                                var eItems = this.closest('.ContentModuleRelationshipEditor').find('select[name=ExistingItems]');

                                if (eItems && eItems.val()) {
                                        this.closest('.cms-content').addClass('loading');
                                        $.ajax({
                                                url:url + '/' + eItems.val(),
                                                method:'POST',
                                                complete:function() {
                                                        self.closest('.cms-content').removeClass('loading');
                                                },
                                                success:function (data) {
							self.closest('.cms-content').removeClass('loading');
                                                        if (data.Status) {
                                                                if (data.Message) {
                                                                        statusMessage(data.Message, 'good');
                                                                }

                                                                if (data.Content) {
									self.closest('.ContentModuleRelationshipEditor').html($(data.Content).html());
								}
								self.closest('form').removeClass('changed');

                                                        }
                                                        else if (data.Message) {
                                                                statusMessage(data.Message, 'bad');
                                                        }

                                                }
                                        });
                                }


                        }
                });

                $('form.ContentRelationshipEditor_Form').entwine({

                        onadd:function() {
                                //this._super();
                        },
                        onremove:function() {
                                this.removeClass('changed');
                                //this._super();
                        },
                        onsubmit: function (e, button) {
                                e.preventDefault();
                                e.stopImmediatePropagation();
                                var form = this;
                                var self = this;

                                // look for save button
                                if (!button) button = this.find('.Actions :submit[name=action_save]');
                                // default to first button if none given - simulates browser behaviour
                                if (!button) button = this.find('.Actions :submit:first');

                                form.trigger('beforesubmitform');
                                this.trigger('submitform', {form: form, button: button});

                                // set button to "submitting" state
                                $(button).addClass('loading');

                                // get all data from the form
                                var formData = form.serializeArray();
                                // add button action
                                formData.push({name: $(button).attr('name'), value: '1'});


                                this.closest('.cms-content').addClass('loading');
                                // Standard Pjax behaviour is to replace the submitted form with new content.
                                // The returned view isn't always decided upon when the request
                                // is fired, so the server might decide to change it based on its own logic,
                                // sending back different `X-Pjax` headers and content
                                jQuery.ajax({
                                        headers: {"X-Pjax": "CurrentForm,Breadcrumbs"},
                                        url: form.attr('action'),
                                        data: formData,
                                        type: 'POST',
                                        complete: function () {
                                                self.closest('.cms-content').removeClass('loading');
                                                $(button).removeClass('loading');
                                        },
                                        success: function (data, status, xhr) {
                                                form.removeClass('changed'); // TODO This should be using the plugin API

                                                if (data.Status) {
                                                        if (data.Message) {
                                                                statusMessage(data.Message, 'good');
                                                        }

                                                        if (data.Content) {
                                                                dialog.html('');
								dialog.html($(data.Content).html());
                                                        }

							if (currentEditor) currentEditor.reload();
                                                }
                                                else if (data.Message) {
                                                        statusMessage(data.Message, 'bad');
                                                }

                                                //var newContentEls = self.handleAjaxResponse(data, status, xhr);
                                                //if(!newContentEls) return;

                                                //newContentEls.filter('form').trigger('aftersubmitform', {status: status, xhr: xhr, formData: formData});
                                        }
                                });

                                return false;
                        }
                });

                /*$('#Form_EditForm_action_save').entwine({
                        onclick:function(e) {
                                //alert('here');
                        }
                });*/
        });
})(jQuery);