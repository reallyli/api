<template>
    <card class="flex flex-col items-center justify-center">
        <div id="map"></div>
        <div id="stats">
            <table>
                <tr>
                    <td>
                        <b>{{ __("Total businesses:") }}</b>
                    </td>
                    <td class="pr-2">{{ businessTotalOnMap }}</td>
                    <td>
                        <b>{{ __("Total reviews:") }}</b>
                    </td>
                    <td id="reviewTotal" class="pr-2">
                        {{ reviewTotalOnMap }}
                    </td>
                    <td>
                        <b>{{ __("Total images:") }}</b>
                    </td>
                    <td id="imageTotal">{{ imageTotalOnMap }}</td>
                </tr>
            </table>
        </div>
    </card>
</template>

<script>
import mapboxgl from "mapbox-gl";
import MapboxGeocoder from "@mapbox/mapbox-gl-geocoder";
import * as Cookie from "js-cookie";
import queryString from "query-string";

import "../../../node_modules/mapbox-gl/dist/mapbox-gl.css";
import "../../../node_modules/@mapbox/mapbox-gl-geocoder/dist/mapbox-gl-geocoder.css";

const API_KEY =
    "pk.eyJ1IjoiYXNzZCIsImEiOiJjam4waHV1M2kwYXRpM3VwYzYyaTV6em5wIn0.JuV5MaCB2t0sdAgwxrJVbQ";

export default {
    props: ["card"],
    data() {
        return {
            map: null,
            index: "",
            iControl: 0,
            mapData: {
                type: "FeatureCollection",
                features: []
            },
            progress: 0,
            imageTotal: 0,
            reviewTotal: 0,
            businessTotal: 0
        };
    },

    mounted() {
        this.createMap();

        this.index = this.getResourceIndex(this.$parent);

        this.requestGeoJson();

        Echo.channel(`map-data.${Nova.config.userId}`).listen(
            "MapUpdated",
            e => {
                if (e.businesses == "done") {
                    this.progress = this.businessTotal;

                    let interval = setInterval(() => {
                        if (this.map.isSourceLoaded("places")) {
                            this.map.getSource("places").setData(this.mapData);

                            // resource table is updated earlier than map
                            // Once map is rendered, we need to update the table.
                            this.updateIndexResources();

                            clearInterval(interval);
                        }
                    }, 500);
                } else {
                    this.addMapData(e.businesses);

                    this.progress = e.businesses.progress;
                }
            }
        );
    },

    computed: {
        businessTotalOnMap() {
            return this.formatNumber(this.progress);
        },

        reviewTotalOnMap() {
            return this.formatNumber(this.reviewTotal);
        },

        imageTotalOnMap() {
            return this.formatNumber(this.imageTotal);
        }
    },

    methods: {
        requestGeoJson() {
            Nova.request()
                .get(this.geoJsonUrl())
                .then(response => {
                    this.businessTotal = response.data.businessTotal;
                    this.reviewTotal = response.data.reviewTotal;
                    this.imageTotal = response.data.imageTotal;
                });
        },

        setCookie(name, value, hours = 1) {
            Cookie.set(name, value, { expires: 1 });
        },

        getCookie(name) {
            return Cookie.get(name);
        },

        getCenter() {
            if (!this.getCookie("map_position")) {
                return {
                    zoom: 7,
                    center: {
                        lat: 51.877827,
                        lng: 0.764215
                    }
                };
            }

            return JSON.parse(this.getCookie("map_position"));
        },

        geoJsonUrl() {
            return `/api/v1/businesses/geo-json?bounds=${this.map
                .getBounds()
                .toArray()}&id=${Nova.config.userId}`;
        },

        createMap() {
            mapboxgl.accessToken = API_KEY;

            this.map = new mapboxgl.Map({
                container: "map",
                style: "mapbox://styles/mapbox/streets-v9",
                minZoom: 4,
                center: [
                    this.getCenter().center.lng,
                    this.getCenter().center.lat
                ],
                zoom: this.getCenter().zoom
            });

            this.addClusters();
        },

        addClusters() {
            this.addControl();

            let map = this.map;
            map.addControl(new mapboxgl.NavigationControl());
            map.on("load", () => {
                map.on("click", "clusters", function(e) {
                    var features = map.queryRenderedFeatures(e.point, {
                        layers: ["clusters"]
                    });
                    var clusterId = features[0].properties.cluster_id;
                    map.getSource("places").getClusterExpansionZoom(
                        clusterId,
                        function(err, zoom) {
                            if (err) return;

                            map.easeTo({
                                center: features[0].geometry.coordinates,
                                zoom: zoom
                            });
                        }
                    );
                });

                map.on("mouseenter", "clusters", function() {
                    map.getCanvas().style.cursor = "pointer";
                });

                map.on("mouseleave", "clusters", function() {
                    map.getCanvas().style.cursor = "";
                });

                map.on("mouseenter", "unclustered-point", function() {
                    map.getCanvas().style.cursor = "pointer";
                });

                map.on("click", "unclustered-point", function(e) {
                    var coordinates = e.features[0].geometry.coordinates.slice();
                    var description = e.features[0].properties.name;

                    while (Math.abs(e.lngLat.lng - coordinates[0]) > 180) {
                        coordinates[0] +=
                            e.lngLat.lng > coordinates[0] ? 360 : -360;
                    }

                    new mapboxgl.Popup()
                        .setLngLat(coordinates)
                        .setHTML(description)
                        .addTo(map);
                });

                map.on(
                    "zoomend",
                    _.debounce(() => {
                        this.setCookie(
                            "map_position",
                            JSON.stringify({
                                zoom: map.getZoom(),
                                center: map.getCenter()
                            })
                        );

                        this.updateMap();
                    }, 500)
                );

                map.on(
                    "dragend",
                    _.debounce(() => {
                        this.setCookie(
                            "map_position",
                            JSON.stringify({
                                zoom: map.getZoom(),
                                center: map.getCenter()
                            })
                        );

                        this.updateMap();
                    }, 500)
                );

                map.addSource("places", {
                    type: "geojson",
                    data: {
                        features: [{ geometry: { coordinates: [] } }] // dummy
                    },
                    cluster: true,
                    clusterMaxZoom: 14,
                    clusterRadius: 50
                });

                map.addLayer({
                    id: "clusters",
                    type: "circle",
                    source: "places",
                    filter: ["has", "point_count"],
                    paint: {
                        "circle-color": [
                            "step",
                            ["get", "point_count"],
                            "#51bbd6",
                            100,
                            "#f1f075",
                            750,
                            "#f28cb1"
                        ],
                        "circle-radius": [
                            "step",
                            ["get", "point_count"],
                            20,
                            100,
                            30,
                            750,
                            40
                        ]
                    }
                });

                map.addLayer({
                    id: "cluster-count",
                    type: "symbol",
                    source: "places",
                    filter: ["has", "point_count"],
                    layout: {
                        "text-field": "{point_count_abbreviated}",
                        "text-font": [
                            "DIN Offc Pro Medium",
                            "Arial Unicode MS Bold"
                        ],
                        "text-size": 12
                    }
                });

                map.addLayer({
                    id: "unclustered-point",
                    type: "circle",
                    source: "places",
                    filter: ["!", ["has", "point_count"]],
                    paint: {
                        "circle-color": "#da0913",
                        "circle-radius": 4,
                        "circle-stroke-width": 1,
                        "circle-stroke-color": "#fff"
                    }
                });
            });
        },

        updateMap() {
            this.initializeMapData();

            this.requestGeoJson();

            // set search bounds
            this.removeControl().addControl();
        },

        initializeMapData() {
            this.mapData = {
                type: "FeatureCollection",
                features: []
            };
            this.progress = 0;
            this.imageTotal = 0;
            this.reviewTotal = 0;
            this.businessTotal = 0;
        },

        addControl() {
            this.map.addControl(
                (this.iControl = new MapboxGeocoder({
                    accessToken: mapboxgl.accessToken,
                    placeholder: "Search for places in the map",
                    bbox: _.flatten(this.map.getBounds().toArray()),
                    proximity: {
                        longitude: this.map.getCenter().lng,
                        latitude: this.map.getCenter().lat
                    }
                }))
            );
        },

        removeControl() {
            this.map.removeControl(this.iControl);

            return this;
        },

        getResourceIndex(parent) {
            // Return the parent if it is a resource index
            if (parent.$options.name === "resource-index") {
                return parent;
            }

            return typeof parent === "undefined"
                ? null
                : this.getResourceIndex(parent.$parent);
        },

        updateIndexResources() {
            // Call the resource updater
            this.index.getResources();
            this.index.getFilters();
        },

        addMapData(businesses) {
            this.mapData.features = [
                ...this.mapData.features,
                ...businesses.features
            ];

            return this.mapData;
        },

        formatNumber(num) {
            return num.toString().replace(/(\d)(?=(\d{3})+(?!\d))/g, "$1,");
        }
    }
};
</script>
