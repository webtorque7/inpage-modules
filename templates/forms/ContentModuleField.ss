<div id="ContentModuleField" data-url="$Link" data-add_new_url="$Link(addNewModule)" data-existing_url="$Link(getExistingModules)" data-add_existing_url="$Link(addExistingModule)" data-sort_url="$Link(sort)" data-module_url="$Link(module)">

        <div class="new-module-fields">
            <div class="new-fields">
                    <label for="ContentModule_ModuleType" class="first">Module Type</label>
                    <span class="field dropdown">
                            <select id="ContentModule_ModuleType" class="no-change-track">
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
                <label for="ContentModule_ExistingModule" class="existing first">Select Existing Module</label>
                <span class="field dropdown existing"><select id="ContentModule_ExistingModule" class="no-change-track"></select></span>
                <button id="btnAddExisting" class="existing">Add Existing</button>

                <label for="ContentModule_btnAddNew" class="existing">or</label>
                <button id="ContentModule_btnAddNew">Add New</button>
            </div>
        </div>


        <div class="current-modules">
                <h2><% _t('CURRENTCONTENTMODULES', 'Current Modules') %></h2>
                <p class="message"><% _t('CONTENTMODULESTOSORT', 'To sort used modules on this page, drag them up and down.') %></p>
                <% if $CurrentModules %>
                        <% loop $CurrentModules %>
                                $EditForm
                        <% end_loop %>
                <% else %>
                <p>You haven't added any modules</p>
                <% end_if %>
        </div>
</div>