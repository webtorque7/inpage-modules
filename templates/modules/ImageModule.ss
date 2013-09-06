<% if $ResizedImages %>
<div class="image-module">
    <ul>
        <% loop $ResizedImages %>
        <li>$Me</li>
        <% end_loop %>
    </ul>
</div>
<% end_if %>