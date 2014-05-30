{if !empty($bug)}
    <h2>{t}File a bug{/t}</h2>
    <form class="form-horizontal">
        <div class="form-group">
            <label for="addon-name" class="col-md-2">
                {t}Addon name:{/t}
            </label>
            <div class="col-md-10">
                <input type="text" placeholder="Super tux" name="addon-name" id="addon-name" class="typeahead form-control">
            </div>
        </div>
        <div class="form-group">
            <label for="bug-title" class="col-md-2">
                {t}Title:{/t}
            </label>
            <div class="col-md-10">
                <input type="text" placeholder="Title" name="bug-title" id="bug-title" class="form-control">
            </div>
        </div>
        <div class="form-group">
            <label for="bug-description" class="col-md-2">
                {t}Description:{/t}
            </label><br>
            <div class="col-md-10">
                <textarea id="bug-description" name="bug-description" class="form-control" rows="10" placeholder="Description"></textarea>
            </div>
        </div>
        <div class="form-group">
            <div class="col-md-offset-2 col-md-2">
                <button type="submit" class="btn btn-info">Submit</button>
            </div>
        </div>
    </form>
{else}
    <div class="alert alert-warning">
        <strong>{t}Warning!{/t}</strong> {t}You must be logged in{/t}
    </div>
{/if}
