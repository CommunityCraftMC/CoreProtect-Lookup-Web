/**
 * Main JavaScript
 *
 * CoreProtect Lookup Web Interface
 * @author Simon Chuu
 * @copyright 2015-2020 Simon Chuu
 * @license MIT License
 * @link https://github.com/CommunityCraftMC/CoreProtect-Lookup-Web
 * @since 1.0.0
 */
(function () {
"use strict";

const A_EX_USER       = 0x0400;
const A_EX_BLOCK      = 0x0800;
const A_EX_ENTITY     = 0x1000;
const A_EX_WORLD      = 0x2000;
const A_ROLLBACK_YES  = 0x4000;
const A_ROLLBACK_NO   = 0x8000;
const A_REV_TIME      = 0x10000;

// Commonly encountered DOM references in an object
const $lookup = {
    form: $("#lookup-form"),
    server: $("#lookup-database"),
    rollbackYes: $("#lookup-rollback-yes"),
    rollbackNo: $("#lookup-rollback-no"),
    x1: $("#lookup-coords-x"),
    y1: $("#lookup-coords-y"),
    z1: $("#lookup-coords-z"),
    x2: $("#lookup-coords2-x"),
    y2: $("#lookup-coords2-y"),
    z2: $("#lookup-coords2-z"),
    r: $("#lookup-coords-radius"),
    coordsLabel: $("#lookup-coords-label"),
    coordsToggle: $("#lookup-coords-toggle"),
    world: $("#lookup-world"),
    user: $("#lookup-user"),
    material: $("#lookup-material"),
    entity: $("#lookup-entity"),
    keyword: $("#lookup-keyword"),
    time: $("#lookup-time"),
    worldEx: $("#lookup-world-exclude"),
    userEx: $("#lookup-user-exclude"),
    materialEx: $("#lookup-material-exclude"),
    entityEx: $("#lookup-entity-exclude"),
    timeRev: $("#lookup-time-rev"),
    limit: $("#lookup-limit"),
    submit: $("#lookup-submit"),
    alert: $("#lookup-alert")
};

const $more = {
    form: $("#more-form"),
    limit: $("#more-limit"),
    submit: $("#more-submit"),
    alert: $("#more-alert")
};

const $tableBody = $("#output-body");
const $queryTime = $("#output-time");
const $pages = $("#row-pages");

// Configuration constants
const dateTimeFormat = config.dateTimeFormat;
const lookupTimeoutMs = (config.timeoutSeconds ? config.timeoutSeconds + 5 : 25) * 1000;
const actionDefinitions = config.actions || {};
const actionGroups = config.actionGroups || {};
const lookupActionBits = Object.keys(actionDefinitions).reduce(function (bits, actionKey) {
    return bits | actionDefinitions[actionKey].bit;
}, 0);

moment.defaultFormat = dateTimeFormat;
$lookup.time.datetimepicker({
    format: dateTimeFormat,
    // https://stackoverflow.com/questions/47618134/bootstrap-datetimepicker-for-bootstrap-4
    icons: {
        time: 'fa fa-clock-o',
        date: 'fa fa-calendar',
        up: 'fa fa-chevron-up',
        down: 'fa fa-chevron-down',
        previous: 'fa fa-chevron-left',
        next: 'fa fa-chevron-right',
        today: 'fa fa-check',
        clear: 'fa fa-trash',
        close: 'fa fa-times'
    }
});


// ##########################
//  Corner and Radius Toggle
// ##########################
const CORNER = "Corner";
const CENTER = "Center";
const RADIUS = "Radius";
$lookup.coordsToggle.click(function () {coordsToggle()});

function coordsToggle(center) {
    const $r = $lookup.r;
    const isCenter = $r.prop("hidden");
    if (center !== false && isCenter) {
        $lookup.r.prop("hidden", false);

        $lookup.coordsLabel.text(CENTER);
        $lookup.coordsToggle.text(RADIUS);

        $lookup.x2.prop("hidden", true);
        $lookup.y2.prop("hidden", true);
        $lookup.z2.prop("hidden", true);
        $lookup.x2.prop("disabled", true);
        $lookup.y2.prop("disabled", true);
        $lookup.z2.prop("disabled", true);
    } else if (center !== true && !isCenter) {
        $lookup.x2.prop("disabled", false);
        $lookup.y2.prop("disabled", false);
        $lookup.z2.prop("disabled", false);
        $lookup.x2.prop("hidden", false);
        $lookup.y2.prop("hidden", false);
        $lookup.z2.prop("hidden", false);

        $lookup.coordsLabel.text(CORNER + ' ' + 1);
        $lookup.coordsToggle.text(CORNER + ' ' + 2);

        $lookup.r.prop("hidden", true);
        $lookup.r.val("");
    }
}


// ##################
//  Date/Time parser
// ##################
// All times are baed on UNIX timestamp (seconds since)
function unixToString(unix) {
    return moment.unix(unix).format(dateTimeFormat);
}

function setLookupTime(unix, reverse) {
    $lookup.time.val(unixToString(unix));
    const checked = $lookup.timeRev.prop("checked");
    if (typeof reverse === "boolean" && reverse !== checked) {
        $lookup.timeRev.prop("checked", reverse);
        $lookup.timeRev.parent().button("toggle", reverse);
    }
}

function getLookupTime() {
    return moment($lookup.time.val(), dateTimeFormat).unix();
}


// ####################
//  Lookup form parser
// ####################
let currentLookup = null;
let currentCount = 0;
let ajaxWaiting = false;
let queryFlags = null;

$lookup.form.submit(function (ev) {
    submit(ev, false);
});

$more.form.submit(function (ev) {
    submit(ev, true);
});

function submit(ev, more) {
    ev.preventDefault();
    if (ajaxWaiting) {
        addAlert("Please wait for the previous lookup to complete.", more, "info");
        return;
    }

    if (more) {
        if (currentLookup == null) {
            addAlert("A lookup is required.", true, "info");
            return;
        }
        serializeMore();
    } else {
        const a = serializeActions();
        if (!a) {
            addAlert("An action is required.", false, "info");
            return;
        }
        serializeLookup(a);
    }

    $.ajax("lookup.php", {
        method: "POST",
        data: currentLookup,
        dataType: "json",
        timeout: lookupTimeoutMs,
        beforeSend: beforeSend,
        success: more ? moreSuccess : lookupSuccess,
        error: more ? moreError : lookupError,
        complete: complete
    });
}

function serializeActions() {
    let a = 0;

    // Serialize Action/a variable
    a |= serializeActionGroup('primary');
    a |= serializeActionGroup('messages');
    a |= serializeActionGroup('items');
    if ($lookup.rollbackYes.prop("checked")) a |= A_ROLLBACK_YES;
    if ($lookup.rollbackNo.prop("checked")) a |= A_ROLLBACK_NO;
    if ($lookup.worldEx.prop("checked")) a |= A_EX_WORLD;
    if ($lookup.userEx.prop("checked")) a |= A_EX_USER;
    if ($lookup.materialEx.prop("checked")) a |= A_EX_BLOCK;
    if ($lookup.entityEx.prop("checked")) a |= A_EX_ENTITY;
    if ($lookup.timeRev.prop("checked")) a |= A_REV_TIME;

    if ((a & lookupActionBits) === 0)
        return 0;
    return a;
}

function serializeActionGroup(groupKey) {
    let bits = 0;
    const group = actionGroups[groupKey] || [];

    for (let i = 0; i < group.length; i++) {
        const action = actionDefinitions[group[i]];
        if (action && $("#" + action.domId).prop("checked"))
            bits |= action.bit;
    }

    return bits;
}

function serializeLookup(actions) {
    if (!actions)
        return;

    currentCount = 0;
    currentLookup = {a: actions};

    let form = $lookup.form.serializeArray();
    for (let i = 0; i < form.length; i++) {
        if (form[i].value !== "")
            currentLookup[form[i].name] = form[i].value;
    }

    delete(form.rollback);

    const rs = $lookup.r.val();
    if (rs !== "") {
        const xs = $lookup.x1.val();
        const ys = $lookup.y1.val();
        const zs = $lookup.z1.val();

        if (xs !== "" && ys !== "" && zs !== "") {
            const r = parseInt(rs);
            const x = parseInt(xs);
            const y = parseInt(ys);
            const z = parseInt(zs);
            currentLookup.x = x - r;
            currentLookup.y = y - r;
            currentLookup.z = z - r;
            currentLookup.x2 = x + r;
            currentLookup.y2 = y + r;
            currentLookup.z2 = z + r;
        }
    }

    const time = $lookup.time.val();
    if (time !== "") {
        currentLookup.t = getLookupTime();
    }

    if (queryFlags !== null) {
        currentLookup.flags = queryFlags;
    }
}

function serializeMore() {
    if (currentLookup === null)
        return;

    let count = Number.parseInt($more.limit.val());
    currentLookup.offset = currentCount;
    if (isNaN(count)) delete (currentLookup.count);
    else currentLookup.count = count;
}

function beforeSend() {
    ajaxWaiting = true;
    $lookup.alert.empty();
    $more.alert.empty();
    $lookup.submit.prop("disabled", true);
    $more.submit.prop("disabled", true);
}

function complete() {
    ajaxWaiting = false;
    $lookup.submit.prop("disabled", false);
}

function lookupSuccess(data) {
    populateTable(data, false);
}

function lookupError(xhr, status, thrown) {
    xhrError(xhr, status, thrown, false);
}

function moreSuccess(data) {
    populateTable(data, true);
}

function moreError(xhr, status, thrown) {
    xhrError(xhr, status, thrown, true);
}


// ####################
//  Result Parser
// ####################
let mapHref;

function xhrError(xhr, status, thrown, more) {
    let text;
    if (status === "parsererror") {
        text = thrown + " (Possible PHP error output before JSON. The server should return a JSON query error; check the PHP/webserver error log.)";
    } else if (status === "timeout") {
        text = "Request timed out. Narrow the area, lower the limit, or add filters.";
    } else if (thrown) {
        text = xhr.status + ": " + thrown;
    } else if (xhr.status === 0) {
        text = "Timed out (Check your internet connection)";
    } else {
        text = xhr.status + "";
    }
    addAlert(text, more, "danger");
}

function populateTable(data, more) {
    $queryTime.text("Request generated in "+Math.round(data[0].duration*1000)+"ms");

    if (data[0].status !== 0) {
        let st = data[0];
        let text;
        if (st.status === 1) {
            text = `${st.code}: ${st.reason}`
        } else if (st.status === 2) {
            text = `${st.code} (${st.driverCode}): ${st.reason}`
        } else {
            text = "Unknown error occured."
        }
        addAlert(text, more, "danger");
        return;
    }

    const rows = data[1];

    if (rows.length === 0) {
        if (more) {
            if ((currentLookup.a & A_REV_TIME ) === 0)
                $tableBody.prepend('<tr><th><i class="fa fa-minus"></i></th><td colspan="5">No more results</td></tr>');
            else {
                addAlert("No more results. (If on a live server, wait a bit then submit for more results)", more, "info");
                $more.submit.prop("disabled", false);
            }
        } else {
            addAlert("That lookup returned no results.", more, "info");
        }
        return;
    }

    if (data[0].mapHref)
        mapHref = data[0].mapHref;

    if (queryFlags === null) {
        queryFlags = currentLookup.flags = data[0].flags;
    }

    if (!more) {
        $tableBody.empty();
        if (!currentLookup.t)
            currentLookup.t = data[1][0].time;
    }

    for (let i = 0; i < rows.length; i++) {
        currentCount++;
        $tableBody.append(populateRow(rows[i]));
    }

    // Allow submitting more lookups
    $more.submit.prop("disabled", false);
}

function addAlert(text, more, level) {
    if (!level)
        level = "warning";
    const $alert = more ? $more.alert : $lookup.alert;

    $alert.prepend(getAlertElement(text, level));
}

function populateRow(row) {
    const ret = document.createElement("tr");

    const rowEl = document.createElement("th");
    rowEl.title = "Row ID: " + row.id;
    rowEl.textContent = currentCount;
    ret.append(rowEl);

    const dateEl = document.createElement("td");
    dateEl.classList.add("dropdown");
    dateEl.textContent = unixToString(row.time) + " ";
    dateEl.append(addDropButton({type: "date", time: row.time}));
    ret.append(dateEl);

    const userEl = document.createElement("td");
    userEl.classList.add("dropdown");
    userEl.textContent = row.user + " ";
    userEl.append(addDropButton({type: "user", user: row.user, uuid: row.uuid}));
    ret.append(userEl);

    const actionGroup = row.actionGroup || row.table;
    switch (actionGroup) {
        case "session":
        case "container":
        case "block":
        case "item":
        case "inventory":
        case "sign":
            ret.append(renderActionCell(row));
            ret.append(renderCoordsCell(row));
            ret.append(renderTargetCell(row));
            break;
        case "chat":
        case "command":
        case "username":
            ret.append(renderActionCell(row));
            ret.append(renderCoordsCell(row));
            ret.append(renderTargetCell(row));
            break;
        default:
            ret.append(renderActionCell(row));
            ret.append(renderCoordsCell(row));
            ret.append(renderTargetCell(row));
    }

    return ret;
}

function renderActionCell(row) {
    const actionEl = document.createElement("td");
    actionEl.append(createBadge(row.actionLabel || row.table, badgeStyle(row)));
    if (row.amount !== null)
        actionEl.append(document.createTextNode(" "), createBadge(row.amount, "secondary"));
    if (row.rolledBack)
        actionEl.append(document.createTextNode(" "), createBadge("Rolled Back", "light"));
    return actionEl;
}

function createBadge(text, style) {
    const badge = document.createElement("span");
    badge.classList.add("badge", "badge-" + style);
    badge.textContent = text;
    return badge;
}

function badgeStyle(row) {
    if (row.actionLabel && row.actionLabel.charAt(0) === "-")
        return "danger";
    if (row.actionLabel && row.actionLabel.charAt(0) === "+")
        return "success";
    if (row.actionGroup === "kill" || row.action === 3 && row.actionGroup === "block")
        return "warning";
    return "info";
}

function renderCoordsCell(row) {
    const coordsEl = document.createElement("td");
    if (row.world !== null) {
        coordsEl.classList.add("dropdown");
        coordsEl.textContent = row.x + ' ' + row.y + ' ' + row.z + ' ' + row.world + ' ';
        coordsEl.append(addDropButton({type: "coordinates", world: row.world, x: row.x, y: row.y, z: row.z}));
    }
    return coordsEl;
}

function renderTargetCell(row) {
    if (row.actionGroup === "sign")
        return renderSignTarget(row);
    if (row.actionGroup === "item" || row.actionGroup === "inventory")
        return renderItemTarget(row);

    const targetEl = document.createElement("td");
    if (row.target === null) {
        appendMetadataList(targetEl, row);
        return targetEl;
    }

    targetEl.textContent = row.target + " ";
    if (row.targetType === "material" || row.targetType === "entity" || row.targetType === "item") {
        const targetAttr = {
            type: row.targetType === "entity" ? "entity" : "material",
            item: row.target
        };
        if (row.data !== null && row.data !== "0") {
            if (row.targetType === "material")
                targetEl.firstChild.textContent = row.target + "[" + row.data + "] ";
            else
                targetAttr.data = row.data;
        }
        targetEl.classList.add("dropdown");
        targetEl.append(addDropButton(targetAttr));
    }

    appendMetadataList(targetEl, row);
    return targetEl;
}

function renderSignTarget(row) {
    const targetEl = document.createElement("td");
    const lines = row.metadata && row.metadata.lines ? row.metadata.lines : [row.target];
    for (let i = 0; i < lines.length; i++) {
        if (lines[i] === null && i > 3)
            continue;
        if (i > 0)
            targetEl.append(document.createElement("br"));
        targetEl.append(document.createTextNode(lines[i] === null ? "" : lines[i]));
    }
    appendMetadataList(targetEl, row);
    return targetEl;
}

function renderItemTarget(row) {
    const targetEl = document.createElement("td");
    if (row.target === null) {
        appendMetadataList(targetEl, row);
        return targetEl;
    }

    let text = row.target;
    if (row.data !== null && row.data !== "0")
        text += "[" + row.data + "]";
    targetEl.textContent = text + " ";
    targetEl.classList.add("dropdown");
    targetEl.append(addDropButton({type: "material", item: row.target}));
    appendMetadataList(targetEl, row);
    return targetEl;
}

function appendMetadataList(targetEl, row) {
    const list = renderMetadataList(row);
    if (list !== null)
        targetEl.append(list);
}

function renderMetadataList(row) {
    const metadata = row.metadata || {};
    const items = [];

    if (metadata.face !== undefined && metadata.face !== null)
        items.push(["Face", metadata.face]);
    if (metadata.waxed !== undefined && metadata.waxed !== null)
        items.push(["Waxed", metadata.waxed ? "yes" : "no"]);
    if (metadata.color !== undefined && metadata.color !== null)
        items.push(["Color", metadata.color]);
    if (metadata.blockMetaHex)
        items.push(["Block meta", shortMetadata(metadata.blockMetaHex)]);
    if (metadata.blockDataHex)
        items.push(["Block data", shortMetadata(metadata.blockDataHex)]);
    if (metadata.containerMetadataHex)
        items.push(["Container metadata", shortMetadata(metadata.containerMetadataHex)]);
    items.push(...renderDecodedMetadataList(metadata.containerMetadataDecoded));
    if (metadata.itemDataHex)
        items.push(["Item data", shortMetadata(metadata.itemDataHex)]);
    items.push(...renderDecodedMetadataList(metadata.itemDataDecoded));

    if (items.length === 0)
        return null;

    const wrapper = document.createElement("div");
    wrapper.classList.add("small", "text-muted", "lookup-metadata");
    items.forEach(([label, value]) => {
        const rowEl = document.createElement("div");
        const labelEl = document.createElement("span");
        labelEl.textContent = label + ": ";
        const valueEl = document.createElement("code");
        valueEl.textContent = String(value);
        rowEl.append(labelEl, valueEl);
        wrapper.append(rowEl);
    });

    return wrapper;
}

function renderDecodedMetadataList(decoded) {
    if (!decoded)
        return [];

    const items = [];
    if (decoded.displayName)
        items.push(["Decoded name", decoded.displayName]);
    if (decoded.metaType)
        items.push(["Metadata type", decoded.metaType]);
    if (decoded.enchants && decoded.enchants.length)
        items.push(["Enchants", decoded.enchants.map(formatDecodedEnchant).join(", ")]);
    if (decoded.itemFlags && decoded.itemFlags.length)
        items.push(["Item flags", decoded.itemFlags.join(", ")]);
    if (decoded.trim)
        items.push(["Trim", [decoded.trim.material, decoded.trim.pattern].filter(Boolean).join(" / ")]);
    if (decoded.publicBukkitValues)
        items.push(["Custom data", Object.keys(decoded.publicBukkitValues).map((key) => key + "=" + decoded.publicBukkitValues[key]).join(", ")]);
    if (decoded.lore && decoded.lore.length)
        items.push(["Lore", decoded.lore.filter((line) => line !== "").slice(0, 6).join(" | ")]);

    return items;
}

function formatDecodedEnchant(enchant) {
    if (!enchant)
        return "";
    if (enchant.level)
        return enchant.name + " " + enchant.level;
    return enchant.name || enchant.id;
}

function shortMetadata(value) {
    const text = String(value);
    if (text.length <= 96)
        return text;
    return text.slice(0, 96) + "... (" + text.length + " hex chars)";
}

function addDropButton(attrMap) {
    const ret = document.createElement("button");
    ret.classList.add("btn", "btn-secondary", "btn-inline", "output-add-dropdown", "dropdown-toggle", "dropdown-toggle-split");
    ret.role = "button";
    for (const prop in attrMap)
        // noinspection JSUnfilteredForInLoop
        ret.dataset[prop] = attrMap[prop];
    ret.dataset.toggle = "dropdown";
    return ret;
}

function makeMapHref(dataset) {
    return mapHref
        .replace("{world}", dataset.world)
        .replace("{x}", dataset.x)
        .replace("{y}", dataset.y)
        .replace("{z}", dataset.z);
}

// ###################
//  Dropdown Listener
// ###################
const LT1 = "lt1";
const LT2 = "lt2";
const LT3 = "lt3";
const frameName = "co_map";

$tableBody.on("click", ".output-add-dropdown", function() {
    const addon = document.createElement("div");
    addon.classList.add("dropdown-menu");
    const lt1 = document.createElement("a");
    const lt2 = document.createElement("a");
    lt1.classList.add("dropdown-item");
    lt2.classList.add("dropdown-item");
    lt1.dataset.fillPos = LT1;
    lt2.dataset.fillPos = LT2;
    lt1.href = lt2.href = "#";

    switch (this.dataset.type) {
        case "date":
            const time = document.createElement("span");
            time.classList.add("dropdown-item-text");
            time.append(document.createTextNode("Unix time: "), codeElement(this.dataset.time));
            lt1.textContent = "Before";
            lt2.textContent = "After";
            addon.append(time);
            break;
        case "user":
            const uuid = document.createElement("span");
            uuid.classList.add("dropdown-item-text");
            uuid.append(document.createTextNode("UUID: "), codeElement(this.dataset.uuid));
            lt1.textContent = "Include";
            lt2.textContent = "Exclude";
            break;
        case "coordinates":
            if (mapHref) {
            const map = document.createElement("a");
                map.classList.add("dropdown-item");
                map.href = makeMapHref(this.dataset);
                map.textContent = "Open in map";
                map.target = frameName;
                addon.append(map);
            }
            const cntr = document.createElement("a");
            cntr.classList.add("dropdown-item");
            cntr.dataset.fillPos = LT3;
            cntr.href = "#";
            cntr.textContent = "Center";
            lt1.textContent = "Corner 1";
            lt2.textContent = "Corner 2";
            addon.append(cntr);
            break;
        case "material":
            lt1.textContent = "Include";
            lt2.textContent = "Exclude";
            break;
        case "entity":
            const enid = document.createElement("span");
            if (this.dataset.data) {
                enid.append(
                    document.createTextNode((this.dataset.data.length === 36 ? "UUID" : "Entity row ID") + ": "),
                    codeElement(this.dataset.data)
                );
                enid.classList.add("dropdown-item-text");
                addon.append(enid);
            }
            lt1.textContent = "Include";
            lt2.textContent = "Exclude";
            break;
    }
    addon.append(lt1);
    addon.append(lt2);

    // Prevent dropdown from collapsing when clicked inside
    $(addon).on("click", ":not(.dropdown-item)", function (e) {
        e.stopPropagation();
    });

    // Prevent dropdown from collapsing when clicked inside
    $(addon).on("click", ".dropdown-item", dropdownAutofill);

    this.after(addon);
    this.classList.remove("output-add-dropdown");
    this.classList.add("output-dropdown");
});

function dropdownAutofill(ev) {
    const fillPos = this.dataset.fillPos;
    if (!fillPos)
        return;

    ev.preventDefault();
    const data = this.parentElement.previousSibling.dataset;
    let $elem, $toggle;
    let item;

    switch (data.type) {
        case "user":
            item = data.user;
            $elem = $lookup.user;
            $toggle = $lookup.userEx;
            break;
        case "material":
            item = data.item;
            $elem = $lookup.material;
            $toggle = $lookup.materialEx;
            break;
        case "entity":
            item = data.item;
            $elem = $lookup.entity;
            $toggle = $lookup.entityEx;
            break;
        case "date":
            setLookupTime(data.time, fillPos === LT2);
            return;
        case "coordinates":
            dropdownCoordsAutofill(data, fillPos);
            return;
    }

    console.log(item);
    console.log($elem.val());
    console.log($toggle.prop("checked"));

    const exclude = fillPos === LT2;
    const checked = $toggle.prop("checked");

    if (checked === exclude)
        $elem.val(csvSetAdd($elem.val(), item));
    else {
        const res = csvSetRemove($elem.val(), item);
        if (res) {
            $elem.val(res);
        } else {
            $toggle.prop("checked", !checked);
            $toggle.parent().button("toggle");
            $elem.val(item);
        }
    }
}

function dropdownCoordsAutofill(data, fillPos) {
    if (fillPos === LT2) {
        coordsToggle(false);
        $lookup.x2.val(data.x);
        $lookup.y2.val(data.y);
        $lookup.z2.val(data.z);
    } else {
        $lookup.x1.val(data.x);
        $lookup.y1.val(data.y);
        $lookup.z1.val(data.z);

        if (fillPos === LT1) {
            coordsToggle(false);
        } else {
            coordsToggle(true);
        }
    }

    $lookup.world.val(data.world);
    $lookup.worldEx.prop("checked", false);
    $lookup.worldEx.parent().button("toggle", false);
}

// ###################
//  Utility Functions
// ###################
function csvSetAdd(text, value) {
    return text === "" ? value : text.split(/ *, */).includes(value) ? text : text + ", " + value;
}

function csvSetRemove(text, value) {
    const parts = text.split(/ *, */);
    let i = parts.indexOf(value);
    if (i === -1) {
        return false;
    } else {
        parts.splice(i, 1);
        return parts.join(", ");
    }
}

function codeElement(text) {
    const code = document.createElement("code");
    code.textContent = text;
    return code;
}

function getAlertElement(title, level) {
    const alert = document.createElement("div");
    alert.classList.add("alert", "alert-" + level, "alert-dismissible");
    alert.role = "alert";
    alert.append(document.createTextNode(title));

    const button = document.createElement("button");
    button.type = "button";
    button.classList.add("close");
    button.dataset.dismiss = "alert";
    button.setAttribute("aria-label", "Close");

    const close = document.createElement("span");
    close.setAttribute("aria-hidden", "true");
    close.textContent = "\u00d7";
    button.append(close);
    alert.append(button);
    return alert;
}

}());
