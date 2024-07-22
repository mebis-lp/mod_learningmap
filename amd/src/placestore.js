let placestore = {
    version: 2024072201,
    id: 0,
    places: [],
    paths: [],
    startingplaces: [],
    targetplaces: [],
    placecolor: '#c01c28',
    strokecolor: '#ffffff',
    strokeopacity: 1,
    textcolor: '#ffffff',
    visitedcolor: '#26a269',
    height: 100,
    width: 800,
    hidepaths: false,
    mapid: '',
    usecheckmark: false,
    editmode: true,
    pulse: false,
    hover: false,
    showall: false,
    showtext: false,
    slicemode: false,
    showwaygone: false,
    /**
     * Loads attributes from JSON into placestore
     * @param {*} json
     */
    loadJSON: function(json) {
        try {
            let fromjson = JSON.parse(json);
            if (fromjson.textcolor === null) {
                fromjson.textcolor = fromjson.strokecolor;
            }
            Object.assign(this, fromjson);
            // eslint-disable-next-line no-empty
        } catch { }
        // Update version (only relevant if learning map is saved)
        this.version = 2024072201;
    },
    /**
     * Returns placestore as a JSON string ()
     * @returns {string}
     */
    buildJSON: function() {
        return JSON.stringify(this.getPlacestore());
    },
    /**
     * Adds a place. If it is the only place, it is set as starting place
     * @param {*} id id of the place
     * @param {*} linkId id of the corresponding link
     * @param {*} linkedActivity course module id of linked activity
     */
    addPlace: function(id, linkId, linkedActivity = null) {
        this.places.push({
            id: id,
            linkId: linkId,
            linkedActivity: linkedActivity,
            placecolor: null,
            visitedcolor: null
        });
        if (this.places.length == 1) {
            this.addStartingPlace(id);
        }
        this.id++;
    },
    /**
     * Removes a place
     * @param {*} id id of the place
     */
    removePlace: function(id) {
        this.removeStartingPlace(id);
        this.removeTargetPlace(id);
        this.places = this.places.filter(
            function(p) {
                return p.id != id;
            }
        );
    },
    /**
     * Adds a place to the array of starting places
     * @param {*} id id of the place
     */
    addStartingPlace: function(id) {
        this.startingplaces.push(id);
    },
    /**
     * Removes a place from the array of starting places
     * @param {*} id id of the place
     */
    removeStartingPlace: function(id) {
        this.startingplaces = this.startingplaces.filter(
            function(e) {
                return e != id;
            }
        );
    },
    /**
     * Returns whether a place is in the array of starting places
     * @param {*} id id of the place
     * @returns {boolean}
     */
    isStartingPlace: function(id) {
        return this.startingplaces.includes(id);
    },
    /**
     * Adds a place to the array of target places
     * @param {*} id id of the place
     */
    addTargetPlace: function(id) {
        this.targetplaces.push(id);
    },
    /**
     * Removes a place from the array of target places
     * @param {*} id id of the place
     */
    removeTargetPlace: function(id) {
        this.targetplaces = this.targetplaces.filter(
            function(e) {
                return e != id;
            }
        );
    },
    /**
     * Returns whether a place is in the array of target places
     * @param {number} id id of the place
     * @returns {boolean}
     */
    isTargetPlace: function(id) {
        return this.targetplaces.includes(id);
    },
    /**
     * Adds a path between two places
     * @param {*} pid id of the path
     * @param {*} fid id of the first place
     * @param {*} sid id of the second place
     */
    addPath: function(pid, fid, sid) {
        this.paths.push({
            id: pid,
            fid: fid,
            sid: sid,
            strokecolor: null,
            strokedasharray: null,
            hidepath: null
        });
    },
    /**
     * Removes a path
     * @param {*} id id of the place
     */
    removePath: function(id) {
        this.paths = this.paths.filter(
            function(p) {
                return p.id != id;
            }
        );
    },
    /**
     * Returns an array of paths touching a place
     * @param {*} id id of the place
     * @returns {array}
     */
    getTouchingPaths: function(id) {
        return this.paths.filter(
            function(p) {
                return p.fid == id || p.sid == id;
            }
        );
    },
    /**
     * Returns the course module id linked to a place
     * @param {*} id id of the place
     * @returns {number} id of the linked course module
     */
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
    /**
     * Sets the id of the linked course module
     * @param {*} id id of the place
     * @param {*} linkedActivity course module id
     */
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
    /**
     * Sets the color of 'stroke', 'place' or 'visited'
     * @param {*} type type of the color
     * @param {*} color color in hex format
     */
    setColor: function(type, color) {
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
            case 'text':
                this.textcolor = color;
                break;
        }
    },
    /**
     * Gets the color of 'stroke', 'place' or 'visited'
     * @param {*} type type of the color
     * @returns {string} color in hex format
     */
    getColor: function(type) {
        switch (type) {
            case 'stroke':
                return this.strokecolor;
            case 'place':
                return this.placecolor;
            case 'visited':
                return this.visitedcolor;
            case 'text':
                return this.textcolor;
        }
        return null;
    },
    /**
     * Returns the current id
     * @returns {number}
     */
    getId: function() {
        return this.id;
    },
    /**
     * Sets the dimensions of the background image
     * @param {*} width
     * @param {*} height
     */
    setBackgroundDimensions: function(width, height) {
        this.width = width;
        this.height = height;
    },
    /**
     * Returns all paths starting at a place
     * @param {*} id id of the place
     * @returns {array}
     */
    getPathsWithFid: function(id) {
        return this.paths.filter(function(p) {
            return p.fid == id;
        });
    },
    /**
     * Returns all paths ending at a place
     * @param {*} id id of the place
     * @returns {array}
     */
    getPathsWithSid: function(id) {
        return this.paths.filter(function(p) {
            return p.sid == id;
        });
    },
    /**
     * Returns the attributes of placestore
     * @returns {object}
     */
    getPlacestore: function() {
        return {
            id: this.id,
            places: this.places,
            paths: this.paths,
            startingplaces: this.startingplaces,
            targetplaces: this.targetplaces,
            placecolor: this.placecolor,
            strokecolor: this.strokecolor,
            strokeopacity: this.strokeopacity,
            textcolor: this.textcolor,
            visitedcolor: this.visitedcolor,
            height: this.height,
            width: this.width,
            hidepaths: this.hidepaths,
            mapid: this.mapid,
            usecheckmark: this.usecheckmark,
            editmode: this.editmode,
            version: this.version,
            pulse: this.pulse,
            hover: this.hover,
            showall: this.showall,
            showtext: this.showtext,
            slicemode: this.slicemode,
            showwaygone: this.showwaygone,
        };
    },
    /**
     * Sets hidepaths attribute
     * @param {boolean} value
     */
    setHidePaths: function(value) {
        this.hidepaths = value;
    },
    /**
     * Returns the value of hidepaths attribute
     * @returns {boolean}
     */
    getHidePaths: function() {
        return this.hidepaths;
    },
    /**
     * Sets pulse attribute
     * @param {boolean} value
     */
    setPulse: function(value) {
        this.pulse = value;
    },
    /**
     * Returns the value of pulse attribute
     * @returns {boolean}
     */
    getPulse: function() {
        return this.pulse;
    },
    /**
     * Sets hover attribute
     * @param {boolean} value
     */
    setHover: function(value) {
        this.hover = value;
    },
    /**
     * Returns the value of hover attribute
     * @returns {boolean}
     */
    getHover: function() {
        return this.hover;
    },
    /**
     * Sets showall attribute
     * @param {boolean} value
     */
    setShowall: function(value) {
        this.showall = value;
    },
    /**
     * Returns the value of showall attribute
     * @returns {boolean}
     */
    getShowall: function() {
        return this.showall;
    },
    /**
     * Returns the mapid
     * @returns {string}
     */
    getMapid: function() {
        return this.mapid;
    },
    /**
     * Returns the value of usecheckmark attribute
     * @returns {boolean}
     */
    getUseCheckmark: function() {
        return this.usecheckmark;
    },
    /**
     * Sets the value of usecheckmark attribute
     * @param {boolean} value
     */
    setUseCheckmark: function(value) {
        this.usecheckmark = value;
    },
    /**
     * Returns an array with all activity ids
     * @returns {array}
     */
    getAllActivities: function() {
        let activities = [];
        this.places.forEach(function(p) {
            if (p.linkedActivity) {
                activities.push(p.linkedActivity);
            }
        });
        return activities;
    },
    /**
     * Sets stroke opacity
     * @param {number} value
     */
    setStrokeOpacity: function(value) {
        this.strokeopacity = value;
    },
    /**
     * Returns the current stroke opacity
     * @returns {number}
     */
    getStrokeOpacity: function() {
        return this.strokeopacity;
    },
    /**
     * Sets stroke opacity to 0
     * @param {number} value
     */
    setHideStroke: function(value) {
        this.strokeopacity = (value ? 0 : 1);
    },
    /**
     * Returns the current stroke opacity
     * @returns {number}
     */
    getHideStroke: function() {
        return this.strokeopacity < 1;
    },
    /**
     * Returns the value of showtext attribute
     * @returns {boolean}
     */
    getShowText: function() {
        return this.showtext;
    },
    /**
     * Sets the value of showtext attribute
     * @param {boolean} value
     */
    setShowText: function(value) {
        this.showtext = value;
    },
    /**
     * Returns an array with all place identifiers
     * @returns {array}
     */
     getPlaces: function() {
        return this.places;
    },
    /**
     * Returns if slicemode is enabled
     * @returns {boolean}
     */
    getSliceMode: function() {
        return this.slicemode;
    },
    /**
     * Sets state of slicemode
     * @param {boolean} value
     */
    setSliceMode: function(value) {
        this.slicemode = value;
    },
    /**
     * Returns if showwaygone is enabled
     * @returns {boolean}
     */
    getShowWayGone: function() {
        return this.showwaygone;
    },
    /**
     * Sets state of showwaygone
     * @param {boolean} value
     */
    setShowWayGone: function(value) {
        this.showwaygone = value;
    },
};

export default placestore;
