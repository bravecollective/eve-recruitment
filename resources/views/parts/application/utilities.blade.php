<div class="row">
    <div class="col-3 offset-3">
        <h4>Check Fitting</h4>
        <form class="form" onsubmit="return checkFit(this)">
            <textarea class="form-control" placeholder="Paste Fitting..." rows="15"></textarea>
            <small>Invalid module names are ignored</small>
            <br />
            <button type="submit" class="btn btn-success">Check Fit</button>
        </form>
    </div>
    <div class="col-3">
        <h4>Check Skillplan</h4>
        <form class="form" onsubmit="return checkSkillplan(this)">
            <textarea class="form-control" placeholder="Paste Skillplan..." rows="15"></textarea>
            <small>Invalid skill names are ignored</small>
            <br />
            <button type="submit" class="btn btn-success">Check Skillplan</button>
        </form>
    </div>
</div>