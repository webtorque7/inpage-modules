<div id="$Name_ContentModuleField" class="content-module-field" data-url="$Link" data-add_new_url="$Link('addNewModule')"
     data-existing_url="$Link('getExistingModules')" data-add_existing_url="$Link('addExistingModule')"
     data-copy_url="$Link('copyModule')" data-sort_url="$Link('sort')" data-module_url="$Link('module')">
    <h2>$Title</h2>

    <div class="new-module-fields">
        <div class="new-fields">
            <label for="$Name_ContentModule_ModuleType" class="first ">Module Type</label>
                    <span class="field dropdown">
                            <select id="$Name_ContentModule_ModuleType"
                                    class="no-change-track content-module-type-dropdown">
                                <option>Select Module Type</option>
                                <% loop $AvailableModules %>
                                    <option value="$ClassName">$i18n_singular_name</option>
                                <% end_loop %>
                            </select>
                    </span>

                    <span class="add-fields">

                    </span>
        </div>
        <div class="add-fields">
            <label for="$Name_ContentModule_ExistingModule" class="existing first">Select Existing Module</label>
            <span class="field dropdown existing"><select id="$Name_ContentModule_ExistingModule"
                                                          class="content-module-existing-dropdown no-change-track"></select></span>
            <br>
            <button class="existing content-module-add-existing">Add Existing</button>
            <button class="existing content-module-copy">Copy</button>
            <label for="$Name_ContentModule_btnAddNew" class="existing">or</label>
            <button id="$Name_ContentModule_btnAddNew" class="content-module-add-new">Add New</button>
        </div>
    </div>


    <div class="current-modules">
        <h3><% _t('CURRENTCONTENTMODULES', 'Current Modules') %></h3>

        <p class="message"><% _t('CONTENTMODULESTOSORT', 'To sort used modules on this page, drag them up and down.') %></p>

        <% if $CurrentModules %>
            <div class="modules">
                <% loop $CurrentModules %>
                    $EditForm
                <% end_loop %>
            </div>

            <div class="content-module-field-actions">
                <div class="Actions">
                </div>
            </div>
        <% else %>
            <p>You haven't added any modules</p>
        <% end_if %>
    </div>
</div>