<div class="module-manager module-types" data-existing-modules-url="$ExistingLink">
    <div class="search field text">
        <input type="search" class="text" incremental placeholder="Search for module">
    </div>
    <% loop $ModuleTypes %>
        <div class="module-type" data-type="$ClassName">
            <h4><span class="module-icon class-$ClassName"> $AddAction</h4>
            <p>$Description</p>
            <div class="existing-modules" data-create-existing-url="$Top.CreateLink"></div>
        </div>
    <% end_loop %>
</div>
