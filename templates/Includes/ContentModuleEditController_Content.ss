<div id="pages-controller-cms-content" class="cms-content center cms-tabset $BaseCSSClasses" data-layout-type="border" data-pjax-fragment="Content" data-ignore-tab-state="true">

	<div class="cms-content-header north">
		<div class="cms-content-header-info">
			<h2>
				<% include CMSBreadcrumbs %>
			</h2>
		</div>

		<div class="cms-content-header-tabs">
			<ul>
				<li class="content-treeview<% if class == 'ContentModuleEditController' %> ui-tabs-active<% end_if %>">
					<a href="$LinkModuleEdit" class="cms-panel-link" title="Form_EditForm" data-href="$LinkModuleEdit">
						<% _t('CMSMain.TabContent', 'Content') %>
					</a>
				</li>
				<!--<li class="content-listview<% if class == 'ContentModuleSettingsController' %> ui-tabs-active<% end_if %>">
					<a href="$LinkModuleSettings" class="cms-panel-link" title="Form_EditForm" data-href="$LinkModuleSettings">
						<% _t('CMSMain.TabSettings', 'Settings') %>
					</a>
				</li>-->
				<li class="content-listview<% if class == 'ContentModuleHistoryController' %> ui-tabs-active<% end_if %>">
					<a href="$LinkModuleHistory" class="cms-panel-link" title="Form_EditForm" data-href="$LinkModuleHistory">
						<% _t('CMSMain.TabHistory', 'History') %>
					</a>
				</li>
			</ul>
		</div>
	</div>

	$EditForm

</div>