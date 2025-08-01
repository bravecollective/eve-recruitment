<div class="row justify-content-center text-center">
    <div class="col-12 checker-output"></div>
</div>
<div class="row justify-content-center">
    <div class="col-12 col-xl-3">
        <h4>Check Fitting</h4>
        <form class="form" onsubmit="return checkFit(this)">
            <textarea class="form-control" placeholder="Paste Fitting..." rows="15"></textarea>
            <small>Invalid module names are ignored</small>
            <br />
            <button type="submit" class="btn btn-success">Check Fit</button>
        </form>
    </div>
    <div class="col-12 col-xl-3">
        <h4>Check Skillplan</h4>
        <form class="form" onsubmit="return checkSkillplan(this)">
            <textarea class="form-control" placeholder="Paste Skillplan..." rows="15"></textarea>
            <small>Invalid skill names are ignored</small>
            <br />
            <button type="submit" class="btn btn-success">Check Skillplan</button>
        </form>
    </div>
    <div class="col-12 col-xl-3">
        <h4>Check Assets</h4>
        <form class="form" onsubmit="return checkAssets(this)">
            <textarea class="form-control" placeholder="Paste Assets..." rows="15"></textarea>
            <small>Invalid item names are ignored</small>
            <br />
            <button type="submit" class="btn btn-success">Check Assets</button>
        </form>
    </div>
</div>