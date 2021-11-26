define('placestore', function() {

    var placestore = {
        id: 0,
        places: [],
        paths: [],
        startingplaces: [],
        targetplaces: [],
        placecolor: '#c01c28',
        strokecolor: '#ffffff',
        visitedcolor: '#26a269',
        height: 100,
        width: 800,
        editmode: true,
        loadJSON: function(json) {
            try {
                let fromjson = JSON.parse(json);
                Object.assign(this, fromjson);
            // eslint-disable-next-line no-empty
            } catch {}
        },
        buildJSON: function() {
            return JSON.stringify(this);
        },
        addPlace: function(id, linkId, linkedActivity = null) {
            this.places.push({
                id: id,
                linkId: linkId,
                linkedActivity: linkedActivity
            });
            if (this.places.length == 1) {
                this.addStartingPlace(id);
            }
            id++;
        },
        removePlace: function(id) {
            this.removeStartingPlace(id);
            this.removeTargetPlace(id);
            this.places = this.places.filter(
                function(p) {
                    return p.id != id;
                }
            );
        },
        addStartingPlace: function(id) {
            this.startingplaces.push(id);
        },
        removeStartingPlace: function(id) {
            this.startingplaces = this.startingplaces.filter(
                function(e) {
                    return e != id;
                }
            );
        },
        isStartingPlace: function(id) {
            return this.startingplaces.includes(id);
        },
        addTargetPlace: function(id) {
            this.targetplaces.push(id);
        },
        removeTargetPlace: function(id) {
            this.targetplaces = this.targetplaces.filter(
                function(e) {
                    return e != id;
                }
            );
        },
        isTargetPlace: function(id) {
            return this.targetplaces.includes(id);
        },
        addPath: function(pid, fid, sid) {
            this.paths.push({
                id: pid,
                fid: fid,
                sid: sid
            });
        },
        removePath: function(id) {
            this.paths = this.paths.filter(
                function(p) {
                    return p.id != id;
                }
            );
        },
        getTouchingPaths: function(id) {
            return this.paths.filter(
                function(p) {
                    return p.fid == id || p.sid == id;
                }
            );
        },
        getActivityId: function(id) {
            let place = this.places.filter(
                function(e) {
                    return id == e.id;
                }
            );
            if (place.length > 0) {
                return place[0].linkedActivity;
            } else {
                return null;
            }
        },
        setActivityId: function(id, linkedActivity) {
            let place = this.places.filter(
                function(e) {
                    return id == e.id;
                }
            );
            if (place.length > 0) {
                place[0].linkedActivity = linkedActivity;
            }
        },
        setColor(type, color) {
            switch (type) {
                case 'stroke':
                    this.strokecolor = color;
                break;
                case 'place':
                    this.placecolor = color;
                break;
                case 'visited':
                    this.visitedcolor = color;
                break;
            }
        },
        getColor(type) {
            switch (type) {
                case 'stroke':
                    return this.strokecolor;
                case 'place':
                    return this.placecolor;
                case 'visited':
                    return this.visitedcolor;
            }
            return null;
        },
        getId() {
            return this.id;
        },
        setBackgroundDimensions(width, height) {
            this.width = width;
            this.height = height;
        }

    };

    return {
        loadJSON: placestore.loadJSON,
        buildJSON: placestore.buildJSON,
        addPlace: placestore.addPlace,
        removePlace: placestore.removePlace,
        addStartingPlace: placestore.addStartingPlace,
        removeStartingPlace: placestore.removeStartingPlace,
        isStartingPlace: placestore.isStartingPlace,
        addTargetPlace: placestore.addTargetPlace,
        removeTargetPlace: placestore.removeTargetPlace,
        isTargetPlace: placestore.isTargetPlace,
        addPath: placestore.addPath,
        removePath: placestore.removePath,
        getTouchingPaths: placestore.getTouchingPaths,
        getActivityId: placestore.getActivityId,
        setActivityId: placestore.setActivityId,
        setColor: placestore.setColor,
        getColor: placestore.getColor,
        getId: placestore.getId,
        setBackgroundDimensions: placestore.setBackgroundDimensions
    };
  });