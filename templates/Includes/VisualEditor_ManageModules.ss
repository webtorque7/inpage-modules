<div class="module-manager">


    <div class="ss-tabset tabset">
        <ul class="ui-tabs-nav">
            <% loop $ModuleComponents %>
                <li class="$FirstLast"><a href="#Modules_{$Relationship}" class="ui-tabs-anchor">$Title</a></li>
            <% end_loop %>
        </ul>

        <% loop $ModuleComponents %>
            <div id="Modules_{$Relationship}" class="tab ui-tabs-panel">
                <div class="cms-actions-buttons-row">
                    <a class="ss-ui-button tool-button font-icon-plus add-button"
                       href="$Top.Link('add')/$Page.ID/$Relationship" aria-disabled="false"><span
                            class="ui-button-text">Add new</span></a>
                </div>
                <p class="message info">Drag and drop the modules to sort them. Changes in order are applied to the live site immediately</p>
                <div class="module-sorter" data-sort-url="$Top.Link('sort')/$Page.ID/$Relationship">
                    <% loop $Modules %>
                        <div class="module" data-id="$ID">
                            <h4>$Title - $singular_name</h4>
                            <span class="links">
                                <a href="$Top.EditLink($ID)" class="edit-link" title="Edit $Title"><i class="fa fa-edit"></i></a>
                                <a href="$Top.Link('unlink')/$ID/$Up.Page.ID/$Up.Relationship" class="unlink" title="Unlink $Title from page $Up.Page.Title"><i class="fa fa-chain"></i></a>
                            </span>
                        </div>
                    <% end_loop %>
                </div>
            </div>
        <% end_loop %>
    </div>


</div>