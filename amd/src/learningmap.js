/* eslint-disable require-jsdoc */
export const init = () => {
    var selectedElement, offset;

    var firstPlace = null,
        secondPlace = null,
        lastTarget = null;

    var elementForActivitySelector = null;

    let mapdiv = document.getElementById('learningmap-editor-map');
    let code = document.getElementById('id_introeditor_text');
    let activitySetting = document.getElementById('learningmap-activity-setting');
    let activitySelector = document.getElementById('learningmap-activity-selector');
    let activityStarting = document.getElementById('learningmap-activity-starting');
    if (activitySelector) {
        activitySelector.addEventListener('change', function() {
            setActivityIdInPlacestore(elementForActivitySelector, activitySelector.value);
        });
        activityStarting.addEventListener('change', function() {
            if (activityStarting.checked) {
                placestore.startingplaces.push(elementForActivitySelector);
            } else {
                placestore.startingplaces = placestore.startingplaces.filter(function(e) {
                    return e != elementForActivitySelector;
                });
            }
            updateCode();
        });
    }

    var placestore;
    try {
        placestore = JSON.parse(document.getElementsByName('placestore')[0].value);
        refreshBackgroundImage();
    } catch {
        placestore = {
            id: 0,
            places: [],
            paths: [],
            startingplaces: [],
            placecolor: 'red',
            strokecolor: 'white',
            height: 100,
            width: 800
        };
    }

    mapdiv.innerHTML = code.value;

    refreshBackgroundImage();

    mapdiv.addEventListener('dblclick', dblclickHandler);
    mapdiv.addEventListener('click', clickHandler);

    mapdiv.addEventListener('contextmenu', function(e) {
        showContextMenu(e);
        e.preventDefault();
    }, false);

    function showContextMenu(e) {
        if (elementForActivitySelector) {
            document.getElementById(elementForActivitySelector).classList.remove('selected2');
        }
        if (activitySetting) {
            if (e.target.classList.contains('place')) {
                e.target.classList.add('selected2');
                let activityId = getActivityIdFromPlacestore(e.target.id);
                activitySetting.setAttribute('style', 'top: ' + e.offsetY + 'px; left: ' + e.offsetX + 'px;');
                activitySetting.removeAttribute('hidden');
                document.getElementById('learningmap-activity-selector').value = activityId;
                if (placestore.startingplaces.includes(e.target.id)) {
                    document.getElementById('learningmap-activity-starting').setAttribute('checked', 'on');
                } else {
                    document.getElementById('learningmap-activity-starting').removeAttribute('checked', '');
                }
                elementForActivitySelector = e.target.id;
            } else {
                hideContextMenu();
            }
        }
    }

    function hideContextMenu() {
        let e = document.getElementById(elementForActivitySelector);
        if (e) {
            e.classList.remove('selected2');
        }
        activitySetting.setAttribute('hidden', '');
    }

    let backgroundfileNode = document.getElementById('id_introeditor_itemid_fieldset');
    let observer = new MutationObserver(refreshBackgroundImage);

    observer.observe(backgroundfileNode, {attributes: true, childList: true, subtree: true});

    makeDraggable(document.getElementById('learningmap_svgmap'));

    let background = document.getElementById('learningmap-background-image');

    background.addEventListener('load', function() {
        let height = parseInt(background.getBBox().height);
        let width = background.getBBox().width;
        placestore.height = height;
        placestore.width = width;
        updateCode();
        processPlacestore();
    });

    function makeDraggable(el) {
        el.addEventListener('mousedown', startDrag);
        el.addEventListener('mousemove', drag);
        el.addEventListener('mouseup', endDrag);
        el.addEventListener('mouseleave', endDrag);
        el.addEventListener('touchstart', startDrag);
        el.addEventListener('touchmove', drag);
        el.addEventListener('touchend', endDrag);
        el.addEventListener('touchleave', endDrag);
        el.addEventListener('touchcancel', endDrag);

        function getMousePosition(evt) {
            var CTM = el.getScreenCTM();
            if (evt.touches) {
                evt = evt.touches[0];
            }
            return {
                x: (evt.clientX - CTM.e) / CTM.a,
                y: (evt.clientY - CTM.f) / CTM.d
            };
        }

        function startDrag(evt) {
            if (evt.target.classList.contains('draggable')) {
                selectedElement = evt.target;
                offset = getMousePosition(evt);
                offset.x -= parseFloat(selectedElement.getAttributeNS(null, "cx"));
                offset.y -= parseFloat(selectedElement.getAttributeNS(null, "cy"));
            }
        }

        function drag(evt) {
            if (selectedElement) {
                evt.preventDefault();
                var coord = getMousePosition(evt);
                let cx = coord.x - offset.x;
                let cy = coord.y - offset.y;
                selectedElement.setAttributeNS(null, "cx", cx);
                selectedElement.setAttributeNS(null, "cy", cy);
                let upd1 = placestore.paths.filter(function(p) {
                    return p.fid == selectedElement.id;
                });
                upd1.forEach(function(p) {
                    let d = document.getElementById(p.id);
                    if (!(d === null)) {
                        d.setAttribute('x1', cx);
                        d.setAttribute('y1', cy);
                    }
                });
                let upd2 = placestore.paths.filter(function(p) {
                    return p.sid == selectedElement.id;
                });
                upd2.forEach(function(p) {
                    let d = document.getElementById(p.id);
                    if (!(d === null)) {
                        d.setAttribute('x2', cx);
                        d.setAttribute('y2', cy);
                    }
                });
            }
        }

        function endDrag() {
            selectedElement = false;
            updateCode();
        }
    }

    function updateCode() {
        let mapdiv = document.getElementById('learningmap-editor-map');
        let code = document.getElementById('id_introeditor_text');
        code.innerHTML = mapdiv.innerHTML;
        document.getElementsByName('placestore')[0].value = JSON.stringify(placestore);
    }

    function dblclickHandler(event) {
        hideContextMenu();
        if (event.target.classList.contains('learningmap-mapcontainer') ||
            event.target.classList.contains('learningmap-background-image')) {
            addPlace(event);
        } else if (event.target.classList.contains('place')) {
            if (lastTarget == event.target.id) {
                lastTarget = null;
                clickHandler(event);
            } else {
                removePlace(event);
            }
        } else if (event.target.classList.contains('path')) {
            removePath(event.target.id);
        }
        updateCode();
    }

    function title(id) {
        let title = document.createElementNS('http://www.w3.org/2000/svg', 'title');
        title.setAttribute('id', id);
        return title;
    }

    function circle(x, y, r, classes, id) {
        let circle = document.createElementNS('http://www.w3.org/2000/svg', 'circle');
        circle.setAttribute('class', classes);
        circle.setAttribute('id', id);
        circle.setAttribute('cx', x);
        circle.setAttribute('cy', y);
        circle.setAttribute('r', r);
        return circle;
    }

    function line(x1, y1, x2, y2, classes, id) {
        let line = document.createElementNS('http://www.w3.org/2000/svg', 'line');
        line.setAttribute('class', classes);
        line.setAttribute('id', id);
        line.setAttribute('x1', x1);
        line.setAttribute('y1', y1);
        line.setAttribute('x2', x2);
        line.setAttribute('y2', y2);
        return line;
    }

    function link(child, id, title = null) {
        let link = document.createElementNS('http://www.w3.org/2000/svg', 'a');
        link.setAttribute('id', id);
        link.setAttribute('xlink:href', '');
        link.appendChild(child);
        if (!(title === null)) {
            link.appendChild(title);
        }
        return link;
    }

    function addPlace(event) {
        let placesgroup = document.getElementById('placesGroup');
        let placeId = 'p' + placestore.id;
        let linkId = 'a' + placestore.id;
        var CTM = event.target.getScreenCTM();
        if (event.touches) {
            event = event.touches[0];
        }
        let cx = (event.clientX - CTM.e) / CTM.a;
        let cy = (event.clientY - CTM.f) / CTM.d;
        placesgroup.appendChild(
            link(
                circle(cx, cy, 10, 'place draggable', placeId),
                linkId,
                title('title' + placeId)
            )
        );
        placestore.places.push({
            id: placeId,
            linkId: linkId,
            linkedActivity: null
        });
        if (placestore.places.length == 1) {
            placestore.startingplaces.push(placeId);
        }
        placestore.id++;
    }

    function clickHandler(event) {
        hideContextMenu();
        if (event.target.classList.contains('place')) {
            event.preventDefault();
            if (firstPlace === null) {
                firstPlace = event.target.id;
                document.getElementById(firstPlace).classList.add('selected');
            } else {
                secondPlace = event.target.id;
                let fid = parseInt(firstPlace.replace('p', ''));
                let sid = parseInt(secondPlace.replace('p', ''));
                if (sid == fid) {
                    return;
                }
                if (sid < fid) {
                    let z = sid;
                    sid = fid;
                    fid = z;
                }
                addPath(fid, sid);
                let first = document.getElementById(firstPlace);
                if (first) {
                    first.classList.remove('selected');
                }
                firstPlace = null;
                lastTarget = secondPlace;
                secondPlace = null;
            }
        } else {
            let p = document.getElementById(firstPlace);
            if (!(p === null)) {
                p.classList.remove('selected');
            }
            firstPlace = null;
        }
    }

    function addPath(fid, sid) {
        let pid = 'p' + fid + '_' + sid;
        if (document.getElementById(pid) === null) {
            let pathsgroup = document.getElementById('pathsGroup');
            let first = document.getElementById('p' + fid);
            let second = document.getElementById('p' + sid);
            if (first && second) {
                pathsgroup.appendChild(
                    line(
                        first.cx.baseVal.value,
                        first.cy.baseVal.value,
                        second.cx.baseVal.value,
                        second.cy.baseVal.value,
                        'path',
                        pid
                    )
                );
                placestore.paths.push({
                    id: pid,
                    fid: 'p' + fid,
                    sid: 'p' + sid
                });
            }
        }
    }

    function removePlace(event) {
        let place = document.getElementById(event.target.id);
        let parent = place.parentNode;
        removePathsTouchingPlace(event.target.id);
        placestore.places = placestore.places.filter(
            function(p) {
                return p.id != event.target.id;
            }
        );
        placestore.startingplaces = placestore.startingplaces.filter(
            function(e) {
                return e != event.target.id;
            }
        );
        parent.removeChild(place);
        parent.parentNode.removeChild(parent);

        updateCode();
    }

    function removePathsTouchingPlace(id) {
        placestore.paths.forEach(
            function(e) {
                if (e.fid == id || e.sid == id) {
                    removePath(e.id);
                }
            }
        );
    }

    function removePath(id) {
        let path = document.getElementById(id);
        if (!(path === null)) {
            path.parentNode.removeChild(path);
            removePathFromPlacestore(id);
        }
    }

    function removePathFromPlacestore(pid) {
        placestore.paths = placestore.paths.filter(
            function(e) {
                return pid != e.id;
            }
        );
    }

    function getActivityIdFromPlacestore(id) {
        let place = placestore.places.filter(
            function(e) {
                return id == e.id;
            }
        );
        if (place.length > 0) {
            return place[0].linkedActivity;
        } else {
            return null;
        }
    }

    function setActivityIdInPlacestore(id, linkedActivity) {
        let place = placestore.places.filter(
            function(e) {
                return id == e.id;
            }
        );
        if (place.length > 0) {
            place[0].linkedActivity = linkedActivity;
        }
        updateCode();
    }

    function refreshBackgroundImage() {
        let previewimage = document.getElementsByClassName('realpreview');
        if (previewimage.length > 0) {
            let background = document.getElementById('learningmap-background-image');
            background.setAttribute('xlink:href', previewimage[0].getAttribute('src').split('?')[0]);
        }
    }

    function processPlacestore() {
        let svg = document.getElementById('learningmap_svgmap');
        // svg.setAttribute('width', placestore.width);
        // svg.setAttribute('height', placestore.height);
        svg.setAttribute('viewBox', '0 0 ' + placestore.width + ' ' + placestore.height);
        //let container = document.getElementById('learningmap-editor-map');
        //container.setAttribute('style', 'height: ' + (placestore.height + 4) + 'px');
    }

};
