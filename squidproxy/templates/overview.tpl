<link href="{$assets_link}css/client-style.css" rel="stylesheet">
<script src="{$assets_link}js/client-script.js"></script>


{if $status eq 200  && $serviceid}

<section>
    <div class='add_hdr'>
        <div class='add_nav'>
            <ul>
                <li><a class='ad_home active'><i class="fa fa-server"></i> Info</a></li>
            </ul>
        </div>
    </div>
</section>
<div class="container deviceCell">

    <h4>Proxy Information</h4>
    <table class="ad_on_table_dash table table-striped" width="100%" cellspacing="0" cellpadding="0" border="0">
        <tbody>
            <tr>
                <td class="hading-td">Username :</td>
                <td class="hading-td">{$username}</td>
            </tr>
            <tr>
                <td class="hading-td">Password :</td>
                <td class="hading-td">
                    <span id="passwordField" class="hidden-password">.................</span>
                    <button id="togglePassword" onclick="togglePassword()"
                        style="border: none; background: none; cursor: pointer; outline: none;"
                        data-password="{$password}">
                        <i id="eyeIcon" class="fa fa-eye"></i>
                    </button>
                </td>
            </tr>
            <tr>
                <td class="hading-td">Proxy List :</td>
                <td class="hading-td text-area">
                    <div class="list-textarea">
                        {foreach from=$allocations item=proxy key=index}
                            <span>{$proxy}</span> </br>
                        {/foreach}
                    </div>
                </td>
            </tr>
        </tbody>
    </table>

</div>

{else}
<div class="alert alert-warning" role="alert">
    Something went wrong. Please contact the admin...
</div>

{/if}