<template>
  <div>
    <div class="google-map" :id="mapName"></div>
    <div class="row">
      <div class="col-md-12">
        <p><span class="badge">Satellite Coordinates</span>{{ latitudeSatellite + ', ' + longitudeSatellite }} | <span class="badge">Illustration Coordinates</span>{{ latitudeIllustration + ', ' + longitudeIllustration }}</p>
        <template v-if="showButtons">
          <button class="btn btn-success" type="button" @click="setLocation">Set location</button>
          <button class="btn btn-default" type="button" @click="resetMarker">Reset</button>
        </template>
        <hr />
      </div>
    </div>
  </div>
</template>
<style scoped>
  .google-map {
    width: 100%;
    height: 600px;
    margin: 0 auto;
    background: gray;
  }
</style>
<script>
    export default {
      created() {},
      mounted() {
        /** TUTORIAL: http://jsfiddle.net/fatihacet/CKegk/ **/
        const self = this
        const element = document.getElementById(this.mapName)
        const defaultOptions = {
      		center: new google.maps.LatLng(42.250570, -83.620748),
      		zoom: 16,
      		streetViewControl: true
      	}
      	const illustratedOptions = {
      		center: new google.maps.LatLng(61.70025812425445, -112.138671875),
      		zoom: 4,
      		streetViewControl: false
      	}
      	const illustratedTypeOptions = {
      		getTileUrl: function(coord, zoom) {
      			return '/tile.php?zoom=' + zoom + '&x=' + coord.x + '&y=' + coord.y
      		},
      		maxZoom: 6,
      		minZoom: 3,
      		tileSize: new google.maps.Size(256, 256),
      		name: 'EMU Illustrated'
      	}
        const illustratedMapType = new google.maps.ImageMapType(illustratedTypeOptions)
        const options = {
          zoom: 16,
          center: new google.maps.LatLng(42.24782481187385, -83.62301669499783),
          mapTypeControlOptions: {
          	mapTypeIds : ["hybrid", "roadmap", "satellite", "illustrated"]
        	},
        }
        const map = new google.maps.Map(element, options)
        map.mapTypes.set('illustrated', illustratedMapType);

        // Add markers based on lat/lng values passed to this component
        if(this.latitudeSatellite && this.longitudeSatellite){
          this.addMarker({lat: this.latitudeSatellite, lng: this.longitudeSatellite}, map, 'satellite')
        }
        if(this.latitudeIllustration && this.longitudeIllustration){
          this.addMarker({lat: this.latitudeIllustration, lng: this.longitudeIllustration}, map, 'illustrated')
        }

        // Change the marker shown and display options based on the map type
        google.maps.event.addListener(map, "maptypeid_changed", function() {
            var mapType = map.getMapTypeId();
            if(mapType == 'illustrated'){
              self.currentMapType = 'illustrated'
              self.hideMarker('satellite')
              self.showMarker('illustrated', map)
              map.setOptions(illustratedOptions)
            } else {
              self.currentMapType = 'satellite'
              self.hideMarker('illustrated')
              self.showMarker('satellite', map)
              map.setOptions(defaultOptions)
            }
        });

        // Whenever the map is clicked, move the marker to that location
        google.maps.event.addListener(map, 'click', function(event) {
          self.placeMarker(event.latLng, map, map.getMapTypeId());
          self.showButtons = true
        });
      },
      components: {},
      props:{
        name:{
          type: String,
          required: true
        },
        latitudeSatellite:{
          type: Number,
          required: false
        },
        longitudeSatellite:{
          type: Number,
          required: false
        },
        latitudeIllustration:{
          type: Number,
          required: false
        },
        longitudeIllustration:{
          type: Number,
          required: false
        },
      },
      data: function() {
        return {
          currentMapType: 'satellite',
          mapName: this.name + "-map",
          markers: {},
          originalCoordinates: {
            'latitudeSatellite': this.latitudeSatellite,
            'longitudeSatellite': this.longitudeSatellite,
            'latitudeIllustration': this.latitudeIllustration,
            'longitudeIllustration': this.longitudeIllustration,
          },
          showButtons: false,
        }
      },
      computed: {},
      methods: {
        // Adds a marker to the map and push to the array.
        addMarker: function(location, map, mapType) {
          let marker = new google.maps.Marker({
            position: location,
            map: map
          });
          this.markers[mapType] = marker
          this.bindMarkerEvents(marker, mapType)
        },
        bindMarkerEvents: function(marker, mapType) {
            const self = this
            google.maps.event.addListener(marker, "rightclick", function (point) {
              self.deleteMarker(marker, mapType); // remove marker
            });
        },
        deleteMarker: function(marker, markerId) {
          this.hideMarker(markerId)
          delete this.markers[markerId]
        },
        hideMarker(markerId){
          if(this.markers[markerId] != undefined){
            this.markers[markerId].setMap(null)
          }
        },
        placeMarker: function(location, map, mapType) {
          let mapTypeString = 'satellite'
          if(mapType == 'illustrated'){
            mapTypeString = 'illustrated'
          }
          if (this.markers[mapTypeString] == undefined){
            this.addMarker(location, map, mapTypeString)
          }
          else{
            this.markers[mapTypeString].setPosition(location);
          }
          map.setCenter(location);
        },
        resetMarker: function(){
          let originalLocation = {lat: this.originalCoordinates.latitudeSatellite, lng: this.originalCoordinates.longitudeSatellite}
          if(this.currentMapType == 'illustrated'){
            originalLocation = {lat: this.originalCoordinates.latitudeIllustration, lng: this.originalCoordinates.longitudeIllustration}
          }
          this.markers[this.currentMapType].setPosition(originalLocation)
          this.setLocation() // now send the reset location back to the parent
          this.showButtons = false // hide the set and rest buttons
        },
        setLocation: function(){
          if(this.currentMapType == 'illustrated'){
            this.$emit('illustrationMarkerUpdated', this.markers['illustrated'].position)
          } else {
            this.$emit('satelliteMarkerUpdated', this.markers['satellite'].position)
          }
          this.showButtons = false // hide the set and rest buttons
        },
        showMarker(markerId, map){
          if(this.markers[markerId] != undefined){
            this.markers[markerId].setMap(map)
          }
        },
      },
      filters: {},
    }
</script>
