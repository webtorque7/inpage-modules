<div class="field ContentModuleRelationshipEditor $extraClass" data-reload_url="$Link('reload')" <% if $SortField %>data-sort_url="$Link('sort')"<% end_if %>>
    <h3>$Title</h3>

    <% if $ShowAddButton || $ShowAddExistingButton %>
    <div class="cms-actions-row">
		<% if not $HasMaxItems %>
			<% if $ShowAddButton %>
			<a class="action-new ss-ui-button ss-ui-action-constructive ui-button ui-widget ui-state-default ui-corner-all ui-button-text-icon-primary" href="$Link('newitem')" data-icon="add" role="button" aria-disabled="false" tilte="Add new">
				Add new
			</a>
			<% end_if %>

			<% if $ShowAddExistingButton %>
				<% if $ShowAddButton %><span class="or"> OR </span><% end_if %>
				<span class="field dropdown">$ExistingDropdown</span>
				<a class="action-existing ss-ui-button ss-ui-action-constructive ui-button ui-widget ui-state-default ui-corner-all ui-button-text-icon-primary" href="$Link('existingitem')" data-icon="add" role="button" aria-disabled="false">
					Add existing
				</a>
			<% end_if %>
		<% end_if %>
    </div>
    <% end_if %>

    <% if $Items %>
    <table class="ss-gridfield-table">
        <% if $Header %>
        <thead>

            <tr>
                <% if $SortField %>
                <th class="main sort">&nbsp;</th>
                <% end_if %>
                <% loop $Header %>
                <th class="main">$Title $Name</th>
                <% end_loop %>
                <th class="main controls"></th>
            </tr>

        </thead>
        <tfoot>
            <tr>
                <td colspan="$NoColumns">&nbsp;</td>
            </tr>
        </tfoot>

        <% end_if %>
        <tbody class="ss-gridfield-items">
        <% loop $Items %>

            <tr class="$EvenOdd" data-id="$Item.ID">
                <% if $Up.SortField %>
                <td>
                    <span class="cmre-handle"> </span>
                </td>
                <% end_if %>
                <% loop $Fields %><td>$Value</td><% end_loop %>
                <td>
                    <button data-url="$Top.Link('remove', $Item.ID)" title="Unlink" class="remove-link no-label" data-icon="chain--minus">
                    </button>
                    <% if $Up.ShowDeleteButton %><button data-url="$Top.Link('deleteitem', $Item.ID)" title="Delete" class="delete-link no-label" data-icon="delete"><% end_if %>
                    </button>
                    <% if $Up.CanEdit && $Item.CanEdit %>
                    <a href="$Top.Link('edititem', $Item.ID)" title="Edit" class="edit-item edit-link">Edit</a>
                    <% end_if %>
                </td>
            </tr>
        <% end_loop %>
        </tbody>
    </table>
    <% else %>
    <p>No items have been added to $Relation</p>
    <% end_if %>

</div>