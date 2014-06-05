<div id="pages-controller-cms-content" class="cms-content center cms-tabset $BaseCSSClasses" data-layout-type="border" data-pjax-fragment="Content" data-ignore-tab-state="true">

    <div class="cms-content-header north">
        <div class="cms-content-header-info">
                <% include CMSBreadcrumbs %>
        </div>

    </div>

    $Tools

    <div class="cms-content-fields center ui-widget-content cms-panel-padded" data-layout-type="border">


        <div class="cms-content-toolbar">
            <% include ContentModulesController_ContentToolActions %>
        </div>

        <div class="cms-panel-content">
            $EditForm
        </div>
    </div>

</div>