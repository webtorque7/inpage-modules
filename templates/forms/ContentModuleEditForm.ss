
<div class="content-module $ClassName" id="{$ClassName}_{$ID}" data-id="$ID">
        <h4><% if $Title %>$Title - <% end_if %><strong>$i18n_singular_name</strong></h4>
        <div class="form">
                <fieldset class="fields">
                        <% loop $EditFields %>
                                $FieldHolder
                        <% end_loop %>
                </fieldset>
                <div class="Actions">
                    <% loop $EditActions %>
                        $FieldHolder
                    <% end_loop %>
                </div>

        </div>

</div>
