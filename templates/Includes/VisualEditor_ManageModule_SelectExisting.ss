<div class="module-manager select-existing" data-create-url="$Link('createexisting')/$Page.ID/$Relationship/$ModuleType">
    <% loop $Modules %>
    <div class="existing-module" data-id="$ID">
        <h4>$Title - $singular_name</h4>
    </div>
    <% end_loop %>
</div>