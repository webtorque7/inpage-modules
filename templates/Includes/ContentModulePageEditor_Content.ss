
<div id="content-module-page-editor-toolbox" draggable="true">
    <ul>
        <li class="icon-modules"><a href="$Link('manage')/module/$CurrentPage.ID"><i class="fa fa-building" aria-hidden="true"></i></a></li>
        <li class="icon-page"><a href="$Link('page')/page/$CurrentPage.ID"><i class="fa fa-pencil-square-o" aria-hidden="true"></i></a></li>
        <li class="icon-settings"><a href="$Link('page')/settings/$CurrentPage.ID"><i class="fa fa-cogs" aria-hidden="true"></i></a></li>
    </ul>
</div>

<div id="content-module-page-editor-cms-content" class="cms-content east column-hidden $BaseCSSClasses cms-tabset content-module-page-editor" data-layout-type="border"
     data-pjax-fragment="Content" data-ignore-tab-state="true" data-edit-module-url="$Link('module')/show">
    $EditForm


</div>

<div id="content-module-page-editor-cms-form-editor" class="cms-form-editor">
    <div class="header">
        <a href="#" class="back" title="Go back"><i class="fa fa-backward" aria-hidden="true"></i></a>
        <a href="#" class="close" title="Close"><i class="fa fa-close" aria-hidden="true"></i></a>
        <a href="#" class="refresh" title="Refresh preview"><i class="fa fa-refresh" aria-hidden="true"></i></a>
    </div>

    <div class="form"></div>
</div>