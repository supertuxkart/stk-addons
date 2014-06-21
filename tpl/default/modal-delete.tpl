<div class="modal fade" id="modal-delete" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
                <h3>{$modal_delete.header|default:'Delete'}</h3>
            </div>
            <div class="modal-body">
                {if isset($modal_delete.body)}
                    $modal_delete.body
                {else}
                    <p>{t}You are about to delete.{/t}</p>
                    <p>{t}Do you want to proceed?{/t}</p>
                {/if}
            </div>
            <div class="modal-footer">
                <button type="button" id="modal-delete-btn-yes" class="btn btn-danger">{t}Yes{/t}</button>
                <button type="button" data-dismiss="modal" id="modal-delete-btn-no" class="btn btn-default">{t}No{/t}</button>
            </div>
        </div>
    </div>
</div>





