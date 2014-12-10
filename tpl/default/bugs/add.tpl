{if $bug}
    <h2>{t}File a bug{/t}</h2>
    <hr>
    <form class="form-horizontal auto-validation" id="bug-add-form"
          data-bv-feedbackicons-valid="glyphicon glyphicon-ok"
          data-bv-feedbackicons-invalid="glyphicon glyphicon-remove"
          data-bv-feedbackicons-validating="glyphicon glyphicon-refresh">
        <div class="form-group">
            <label for="addon-name" class="col-md-2">
                {t}Addon name:{/t}
            </label>
            <div class="col-md-10">
                <input type="text" placeholder="Super tux" name="addon-name" id="addon-name" class="form-control typeahead"
                       data-bv-notempty="true"
                       data-bv-notempty-message="{t}The addon name is required{/t}">
            </div>
        </div>
        <div class="form-group">
            <label for="bug-title" class="col-md-2">
                {t}Title:{/t}
            </label>
            <div class="col-md-10">
                <input type="text" placeholder="Title" name="bug-title" id="bug-title" class="form-control"
                       data-bv-notempty="true"
                       data-bv-notempty-message="{t}The title is required{/t}"

                       data-bv-stringlength="true"
                       data-bv-stringlength-min="{$bug.title.min}"
                       data-bv-stringlength-max="{$bug.title.max}"
                       data-bv-stringlength-message="{t 1=$bug.title.min 2=$bug.title.max}The title must be between %1 and %2 characters long{/t}">
            </div>
        </div>
        <div class="form-group">
            <label for="bug-description" class="col-md-2">
                {t}Description:{/t}
            </label><br>
            <div class="col-md-10">
                <textarea id="bug-description" name="bug-description" class="form-control" rows="10" placeholder="Description"
                          data-bv-field="bug-description"
                          data-bv-notempty="true"
                          data-bv-notempty-message="{t}The bug description is required{/t}"></textarea>
            </div>
        </div>
        <div class="form-group">
            <div class="col-md-offset-2 col-md-2">
                <input type="hidden" name="action" value="add">
                <button type="submit" class="btn btn-info">Submit</button>
            </div>
        </div>
    </form>
{else}
    <div class="alert alert-warning">
        <strong>{t}Warning!{/t}</strong> {t}You must be {/t}<a href="{$root_location}login.php?return_to={$current_url}">{t}logged in{/t}</a>
    </div>
{/if}
