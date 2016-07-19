<div id="content-module-page-editor" class="visual-editor cms-content center $BaseCSSClasses" data-layout-type="border"
     data-pjax-fragment="Content"
     data-edit-module-url="$Link('module')/show"
>
    <div class="cms-content-header north">
        <div class="cms-content-header-info">
            <div class="breadcrumbs-wrapper" data-pjax-fragment="Breadcrumbs">
                <h2 id="page-title-heading">
                    <a class="cms-panel-link crumb" href="$PageEditLink">Go back to
                        edit &quot;$CurrentPage.Title.XML&quot;</a>
                    <span class="sep">/</span>
                    <span class="cms-panel-link crumb last">Visual Editor for &quot;$CurrentPage.Title.XML&quot;</span>
                </h2>
            </div>

        </div>

        <div id="content-module-page-editor-toolbox" class="visual-editor-toolbox" draggable="true" data-page-url="$Link('edit')/">
            $SiteTreeForm
            <ul>
                <li class="icon-modules"><a href="$Link('manage')/module/$CurrentPage.ID" title="Open module manager"><i
                        class="fa fa-cubes" aria-hidden="true"></i></a></li>
                <li class="icon-page"><a href="$Link('page')/$CurrentPage.ID" title="Edit page"><i
                        class="fa fa-pencil-square-o" aria-hidden="true"></i></a></li>
                <li class="icon-settings"><a href="$Link('settings')/$CurrentPage.ID" title="Edit page settings"><i
                        class="fa fa-cogs" aria-hidden="true"></i></a></li>
            </ul>
        </div>
    </div>



    <div id="content-module-page-editor-cms-preview" class="cms-edit-form center visual-editor-preview"
         data-layout-type="border">

        <div class="visual-editor-preview-scroll center">
            <div class="visual-editor-preview-outer">
                <div class="visual-editor-preview-inner">
                    <iframe src="$CurrentPage.Link?stage=Stage&page-editor=true" height="100%" width="100%"></iframe>
                </div>
            </div>
        </div>

        <div class="cms-content-actions cms-content-controls visual-editor-preview-controls south">
            $SilverStripeNavigator
        </div>
    </div>

    <div id="visual-editor-form"
         class="visual-editor-form east">
        <div class="cms-panel-content" data-layout-type="border">
            <div class="cms-content-header north">
                <div class="cms-content-header-info">
                    <a href="#" class="back" title="Go back"><i class="fa fa-backward" aria-hidden="true"></i></a>
                    <a href="#" class="close" title="Close"><i class="fa fa-close" aria-hidden="true"></i></a>
                    <a href="#" class="refresh" title="Refresh preview"><i class="fa fa-refresh" aria-hidden="true"></i></a>
                </div>
            </div>

            <div class="form center"></div>
        </div>
    </div>

</div>