A3O = function () { 
	// CONSTANTS
	const GAME_NAME = 'big_world';
	
	const BOARD_WIDTH = 4876;
	const BOARD_HEIGHT = 2278;
	const SELECT_GLOW_AMOUNT = 10;
	const UNIT_WIDTH = 24;
	const UNIT_HEIGHT = 24;
	const UNIT_SHADOW = 2;
	
	const DEBUGGING = false;
	const DRAW_BOUNDING_BOXES = DEBUGGING;
	const DRAW_CENTERS = DEBUGGING;
	const DRAW_PLACES = DEBUGGING;
	
	// HELPER FUNCTIONS
	var createCallback = function (limit, fn) {
		var finishedCalls = 0;
		return function() {
			if (++finishedCalls == limit) {
				fn();
	        }
	    };
	}
	
	// based on the C-code from http://www.ecse.rpi.edu/Homepages/wrf/Research/Short_Notes/pnpoly.html
	// added some JS optimizations (reducing scope lookups).
	var isPointInPolygon = function (vertices, x, y) {
		var i, j, c = false;
		var len = vertices.length;
		var iElement, jElement;
		for (i = 0, j = len-1; i < len; j = i++) {
			iElement = vertices[i];
			jElement = vertices[j];
			if ( ( (iElement[1]>y ) != (jElement[1]>y) ) &&
				(x < (jElement[0]-iElement[0]) * (y-iElement[1]) / (jElement[1]-iElement[1]) + iElement[0]) ) {
				c = !c;
			}
		}
		return c;
	}
	
	// Expands a rectangle by expand pixels in each direction
	var expandRectangle = function ( rectangle, expand ) {
		var ul = rectangle.ul, lr = rectangle.lr;
		return { ul: [ ul[0]-expand, ul[1]-expand ], lr: [ lr[0]+expand, lr[1]+expand ] };
	}
	
	
	// A3O GAME OBJECT
	return {
		/**
		 * @var Indicates if the player is currently panning the map.
		 */
		panning: false,
		/**
		 * @var While panning stores the position of the mouse when the panning started.
		 */
		panningStart: {x:0,y:0},
		/**
		 * @var Stores the offset of the viewport
		 */
		viewportOffset: {x:0,y:0},
		/**
		 * @var While panning stores the offset of the viewport when the panning started.
		 */
		viewportOffsetPanningStart: {x:0,y:0},
		/**
		 * @var Stores "grabbed" units (units, which are attached to the mouse cursor). 
		 */
		grabbed: {
			origin: false,
			units: []
		},
		selectedZone: false,
		selectedUnit: false,
		/**
		 * @var Stores all the static ressources of the game, like polygon data, images, etc.
		 */
		ressources: {},
		drawBoard: function( saveBuffer, dirtyRect ) {
			var bufferContext = this.bufferContext;
			bufferContext.save();
			
			if ( dirtyRect ) {
				this.setClipping(dirtyRect);
			}
			
			this.drawBackgroundImages( );
			this.drawZonePolygons( );			
			if ( saveBuffer ) {
				this.boardBuffer.src = this.bufferContext.canvas.toDataURL('image/png');
				//this.boardBufferData = this.bufferContext.getImageData( 0, 0, BOARD_WIDTH, BOARD_HEIGHT );
			}
			bufferContext.restore();
			if (typeof console != 'undefined')
				console.log('drawBoard();');
		},
		drawUnits: function( dirtyRect ) {
			var ressources = this.ressources;
			var zones = ressources.game.zones;
			var sprites = ressources.sprites;
			var bufferContext = this.bufferContext;
			
			bufferContext.save();
			
			if (dirtyRect) {
				this.setClipping( dirtyRect );
			}				
			
			bufferContext.shadowOffsetX = UNIT_SHADOW / 2;
			bufferContext.shadowOffsetY = UNIT_SHADOW / 2;
			bufferContext.shadowBlur = UNIT_SHADOW / 2;
			bufferContext.shadowColor = 'black';
			bufferContext.textBaseline = 'middle';
			//bufferContext.lineWidth = 1;
			bufferContext.fillStyle = 'white';	
			bufferContext.font = 'bold 11pt Sans-serif';
			
			for (var z in zones) {
				var zone = zones[z];
				var places = ressources.polygons[z].places;
				var i = 0;
				
				for(var n in zone) {
					var nation = zone[n];
					
					for(var u in nation) {
						var unit = nation[u];
						var place = places[i++];
						
						if ( unit > 0 ) {
							bufferContext.drawImage( sprites[n][u], place[0], place[1], UNIT_WIDTH, UNIT_HEIGHT );
							if (this.stroke)
								bufferContext.strokeText( unit, place[0] + Math.floor(UNIT_WIDTH/4), place[1] + UNIT_HEIGHT );
							bufferContext.fillText( unit, place[0] + Math.floor(UNIT_WIDTH/4), place[1] + UNIT_HEIGHT );
						}
					}
				}
			}
			
			bufferContext.restore();
		},
		clearBoard: function ( dirtyRect ) {
			var bufferContext = this.bufferContext;

			bufferContext.save();
			if ( dirtyRect ) {
				this.setClipping( dirtyRect );
			}			
			//bufferContext.putImageData( this.boardBufferData, 0, 0 );
			bufferContext.drawImage ( this.boardBuffer, 0, 0, BOARD_WIDTH, BOARD_HEIGHT );
			//this.drawBoard( false );
			bufferContext.restore();
			
		},
		drawInterface: function( clipRect ) {
			var bufferContext = this.bufferContext;
			var selectedZone = this.selectedZone;
			var selectedUnit = this.selectedUnit;
			
			// draw selected zone
			if ( selectedZone ) {
				var polygon = this.ressources.polygons[selectedZone].polygon;
				var length = polygon.length;
				var currentPolygon = null;
				
				bufferContext.save();
				if ( clipRect ) {
					this.setClipping( clipRect );
				}
				bufferContext.strokeStyle = 'black';
				bufferContext.lineWidth = 5;
				bufferContext.fillStyle = 'red';
				bufferContext.shadowOffsetX = 0;
				bufferContext.shadowOffsetY = 0;
				bufferContext.shadowOffsetY = 0;
				bufferContext.shadowBlur = SELECT_GLOW_AMOUNT;
				bufferContext.shadowColor = 'red';
				
				bufferContext.beginPath();
				
				currentPolygon = polygon[0];
				bufferContext.moveTo( currentPolygon[0], currentPolygon[1] );
				
				for( var i = 1; i < length; i++ ) {
					currentPolygon = polygon[i];
					bufferContext.lineTo( currentPolygon[0], currentPolygon[1] );
				}
				
				bufferContext.closePath();
				bufferContext.stroke();
				//bufferContext.fill();
				
				bufferContext.restore();
			}
			
			if ( selectedUnit ) {
				var image = this.ressources.sprites[selectedUnit.nation][selectedUnit.unit];
				var rect = this.getPlaceRect( selectedUnit.zone, selectedUnit.place );
				
				bufferContext.save();
				bufferContext.globalCompositeOperation = 'lighter';
				bufferContext.globalAlpha = 0.3;
				
				bufferContext.drawImage( image, rect.ul[0], rect.ul[1], UNIT_WIDTH, UNIT_HEIGHT );
				
				bufferContext.restore();
			}
		},
		drawBackgroundImages: function ( clipRect ) {
			var images = this.ressources.backgroundImages;
		
			for(var image in images) {
				image = images[image];
				this.bufferContext.drawImage(image.image, image.x, image.y);
			}	
		},
		drawZonePolygons: function( clipRect ) {
			var polygons = this.ressources.polygons;
			var bufferContext = this.bufferContext;
			var i, center;
			bufferContext.save();
			
			bufferContext.strokeStyle = 'black';
			bufferContext.lineWidth = 1;
			bufferContext.fillStyle = 'pink';
			c = 0;
			
			for (var polygon in polygons) {
				polygon = polygons[polygon];
				
				
				if ( DRAW_BOUNDING_BOXES ) {
					var ul = polygon.boundingbox.ul, lr = polygon.boundingbox.lr;
					// draw bounding box for debugging
					bufferContext.save();				
					bufferContext.strokeStyle = 'green';				
					bufferContext.beginPath();
					bufferContext.moveTo(ul[0], ul[1]);
					bufferContext.lineTo(ul[0], lr[1]);
					bufferContext.lineTo(lr[0], lr[1]);
					bufferContext.lineTo(lr[0], ul[1]);
					bufferContext.closePath();
					bufferContext.stroke();
					bufferContext.restore();
					// end boundingbox
				}
				
				// do something with the entire polygon structure
				// ...
				
				// skip (s)ea(z)ones
				if (!polygon.sz){
					// get the vertex list
					var vertices = polygon.polygon;
					var length = vertices.length;
					var point = null;
					bufferContext.beginPath();
					
					if (length > 0) {
						point = vertices[0];
						bufferContext.moveTo( point[0], point[1] );	
					}			
					for(var i = 1; i < length; i++){
						point = vertices[i];
						bufferContext.lineTo( point[0], point[1] );
						c++;
					}
					
					bufferContext.closePath();
					bufferContext.fill();
					bufferContext.stroke();
				}
				
				center = polygon.center;
				if ( DRAW_CENTERS ) {
					bufferContext.save();
					bufferContext.fillStyle = 'black';
					bufferContext.beginPath();
					bufferContext.arc( center[0], center[1], 4, 0, 2*Math.PI, true );
					bufferContext.closePath();
					bufferContext.fill();
					bufferContext.restore();
				}
				
				bufferContext.save();
				bufferContext.fillStyle = 'black';
				var nameWidth = (bufferContext.measureText(polygon.name)).width;
				bufferContext.fillText(polygon.name, center[0] - nameWidth/2, center[1]);
				bufferContext.restore();
				
				if (DRAW_PLACES) {
					bufferContext.save();
					bufferContext.fillStyle = 'red';
					places = polygon.places;
					var plen = places.length;
					for (var i = 0; i < plen; i++ ) {
						place = places[i];
						bufferContext.beginPath();
						bufferContext.arc( place[0], place[1], 2, 0, 2*Math.PI, true );
						bufferContext.closePath();
						bufferContext.fill();
						//bufferContext.strokeText(i, place[0], place[1]);
					}
					
					bufferContext.restore();
				}
			}
			bufferContext.restore();
			if (typeof console != 'undefined')
				console.log (c);
		},
		setClipping: function ( clipRect ) {
			var bufferContext = this.bufferContext;
			var ul = clipRect.ul;
			var lr = clipRect.lr;

			bufferContext.beginPath();
			bufferContext.moveTo(ul[0],ul[1]);
			bufferContext.lineTo(ul[0],lr[1]);
			bufferContext.lineTo(lr[0],lr[1]);
			bufferContext.lineTo(lr[0],ul[1]);
			bufferContext.closePath();
			bufferContext.clip();
		},
		swapBuffers: function( ) {
			var viewportContext = this.viewportContext;
			var viewportOffsetX = this.viewportOffset.x;
			var viewportOffsetY = this.viewportOffset.y;
			
			var viewportCanvasWidth = viewportContext.canvas.width; // <- is this DOM touching? I believe so.
			var viewportCanvasHeight = viewportContext.canvas.height; // <- is this DOM touching? I believe so.

			/* Chrome and some other HTML5 browser do not like it, if you
			 * try to draw more of an image than there is data in the eg (draw
			 * 200 pixels of an image that only has 100 pixels) and will throw
			 * an exception if you try to do so (instead of simply not drawing
			 * anything for the out of bounds area).
			 * To prevent this we check if the viewport shows an area that is
			 * out of bounds for the image and if so only draws the remaining
			 * part of the image by cropping how much is drawn.
			 */			
			
			// calculate how much of the image is out of bounds.
			// negative valueds indicate the entire viewport is inside
			// the bounds of the image
			// positive values indicate that the viewport is out of bounds
			// of the image and by how mich pixels it is out of bounds.
			// we can use this value to reduce how much is drawn.
			var viewportOutOfBounds = viewportOffsetX - (BOARD_WIDTH - viewportCanvasWidth);
			
			// if the viewport is past the right edge 
			if ( viewportOutOfBounds > 0 )
			{
				viewportContext.drawImage(
					this.bufferContext.canvas, 
					viewportOffsetX, 
					viewportOffsetY, 
					viewportCanvasWidth - viewportOutOfBounds, // crop how much is drawn by diff
					viewportCanvasHeight, 
					0, 
					0,
					viewportCanvasWidth - viewportOutOfBounds, // crop how much is drawn by diff
					viewportCanvasHeight 
				);
				
				// draw the remainder by beginning over again
				viewportContext.drawImage(
					this.bufferContext.canvas, 
					0,
					viewportOffsetY, 
					viewportCanvasWidth, 
					viewportCanvasHeight, 
					-(viewportOffsetX - BOARD_WIDTH),
					0,
					viewportCanvasWidth, 
					viewportCanvasHeight 
				);
			}
			else
			{
				// the viewport is perfectly within the bounds of the image
				// so simply draw that part of the image.
				viewportContext.drawImage(
					this.bufferContext.canvas, 
					viewportOffsetX, 
					viewportOffsetY, 
					viewportCanvasWidth, 
					viewportCanvasHeight, 
					0, 
					0,
					viewportCanvasWidth, 
					viewportCanvasHeight 
				);
			}
		},
		loadRessources: function ( game, doneCallback ) {
			var delayedCallback = createCallback( 4, doneCallback );
			this.loadPolygons( delayedCallback );
			this.loadBackgroundImages( delayedCallback );
			this.loadSprites( delayedCallback );
			this.loadGameData( game, delayedCallback );
		},
		loadSprites: function ( doneCallback ) {
			var nations = ['Germany','Russia','China','USA','Britain','Japan'];
			var units = ['infantry','armour','factory'];
			var i,j, nlen = nations.length, ulen = units.length;
			
			var delayedCallback = createCallback( nlen * ulen, doneCallback );
			this.ressources.sprites = {};

			for ( i = 0; i < nlen; i++ ) {
				var nation = nations[i];
				this.ressources.sprites[nation] = {};
				for ( j = 0; j < ulen; j++ ) {
					var unit = units[j];
					var sprite = new Image();
					sprite.onload = delayedCallback;
					sprite.src = 'static/images/games/'+GAME_NAME+'/units/' + nation + '/' + unit + '.png';
					this.ressources.sprites[nation][unit] = sprite;
				}
			}
		},
		loadPolygons: function( doneCallback ) {
			var that = this;
			jQuery.getJSON('static/json/games/'+GAME_NAME+'/polygons.json', function( data ) {
					var ressources = that.ressources;
					ressources.placesRT = new RTree();
					ressources.polygons = data;
					for(var p in ressources.polygons) {
						var polygon = ressources.polygons[p];
						var places = polygon.places;
						var plen = places.length;
						
						for(var i = 0; i < plen; i++) {
							var place = places[i];
							ressources.placesRT.insert( 
								{ x: place[0], y: place[1], w: UNIT_WIDTH, h: UNIT_HEIGHT }, 
								{ zone: p, index: i } );
						}
						
						
						
						// HACK
						if (polygon.name.match(/^SZ/))
						{
							polygon.sz = true;
						}
						// HACK END
					}
					doneCallback( );
				}
			);
		},
		loadBackgroundImages: function( doneCallback ) {
			var i = 0, j = 0, imageName = '';
			var delayedCallback = createCallback( (20 * 9) - 10, doneCallback );
			var that = this;
			
			this.ressources.backgroundImages = [];
			for( i = 0; i < 20; i++ ) {
				for( j = 0; j < 9; j++ ) {
					imageName = i + '_' + j + '.png';
					switch(imageName) {
						// those pictures don't exist, no need to try to load them
						case '10_0.png':
						case '10_1.png':
						case '10_3.png':
						case '11_0.png':
						case '18_2.png':
						case '19_1.png':
						case '19_2.png':
						case '19_3.png':
						case '9_1.png':
						case '9_3.png':
							continue;
					}
					var image = new Image( );
					image.onload = (function( ) {
						// close over those variables
						var c_i = i, c_j = j, c_image = image;
						// return a specialized callback using the enclsoed variables
						return function ( ) { 
							// now safe the image after it was loaded; use the specialized (enclosed) varibales!
							that.ressources.backgroundImages.push( { image: c_image, x: c_image.width * c_i, y: c_image.height * c_j } );
							delayedCallback( );
						}
					}) ( ); // invoke the closure that returns the specialized callback
					image.src = 'static/images/games/'+GAME_NAME+'/baseTiles/'+imageName;
				}				
			}
		},
		loadGameData: function ( game, doneCallback ) {
			this.ressources.game = {
					zones : {
						Belorussia: {
							Germany: {
								infantry: 3,
								armour: 1
							},
							Japan: {
								infantry: 1
							}
						},
						WesternGermany: {
							Germany: {
								infantry: 1,
								armour: 1,
								factory: 1
							}
						}
					},
					active: "Germany",
					youAre: "Germany"
			};
			doneCallback();
		},
		transformCoordinates: function (x, y) {
			var offset = jQuery(this.viewportContext.canvas).offset(); // this propably has a fucking big overhead!
			x = x + this.viewportOffset.x - offset.left;
			if ( x > BOARD_WIDTH ) {
				x -= BOARD_WIDTH;
			}				
			y = y + this.viewportOffset.y - offset.top;
			return {x: x, y: y};
		},
		/**
		 * Gets information about the place at page coordinates x, y
		 * @return { zone: the places' zone id, index: the places' index }
		 */
		getPlaceAt: function (x, y) {
			var coords = this.transformCoordinates(x, y);
			var placesRT = this.ressources.placesRT;
			x = coords.x;
			y = coords.y;
			
			var result = placesRT.search( {x: x, y: y, w: 1, h: 1 } );
			if ( result.length ) {
				return result;
			} else {
				return false;
			}
		},
		/**
		 * Gets the bounding rectangle of a place specified by zone and place.
		 * @param zone The places' zones id
		 * @param place The places' index
		 * @return { ul: upper left corner coords, lr: lower right corner coords }
		 */
		getPlaceRect: function ( zone, place ) {
			var p = this.ressources.polygons[zone].places[place];
			return { ul: p, lr: [ p[0] + UNIT_WIDTH, p[1] + UNIT_HEIGHT ] };
		},
		/**
		 * Returns the unit information of a unit in the specified place or false
		 * if no unit is found.
		 * @param zone The places' zone id
		 * @param place The places' index
		 * @return false or unit information object: { unit: id of the unit, nation: nationality of the unit, zone: zone id, see params, place: places' index, see params }
		 */
		getUnitInPlace: function ( zone, place ) {
			var ressources = this.ressources;
			var z = ressources.game.zones[zone];
			var counter = place;
			
			for (var n in z) {
				var nation = z[n];
				for (var u in nation) {
					if (!counter--) {
						if ( nation[u] ) {
							return { unit: u, nation: n, zone: zone, place: place, count: nation[u] };
						} else {
							return false;
						}
					}
				}
			}
			return false;
		},
		/**
		 * Gets the unit information for a unit at page coordinates x, y.
		 * @param x,y Coordinates in page space as provided from jQuery event object (e.pageX, e.pageY)
		 * @return false or unit information object: @see getUnitInPlace
		 */
		getUnitAt: function (x, y) {
			// getPlaceAt CAN return multiple places, we'll just ignore that for now.
			var place = this.getPlaceAt(x, y);
			if (place.length) {
				return this.getUnitInPlace( place[0].zone, place[0].index );
			}
			return false;
		},
		getZoneAt: function (x, y) {
			var polygons = this.ressources.polygons;
			var coords = this.transformCoordinates(x, y);
			x = coords.x;
			y = coords.y;

			for(var p in polygons) {
				// store some local variables to speed things up
				polygon = polygons[p];
				var ul = polygon.boundingbox.ul;
				var lr = polygon.boundingbox.lr;
	
				// see if the selected point lies within the polygons boundingbox
				if ( (ul[0] < x) && (lr[0] > x) ) {
					if ( (ul[1] < y) && (lr[1] > y) )
					{
						// if it does, check if it is really inside the polygon
						if ( isPointInPolygon(polygon.polygon, x, y)) {
							return p;
						}
					}
				}				
			}
			return false;
		},
		selectZone: function (x, y) {
			this.selectedZone = this.getZoneAt(x, y);
		},
		selectUnit: function (x, y) {
			this.selectedUnit = this.getUnitAt(x, y);
		},
		grabUnit: function ( unitInfo ) {
			if ( unitInfo ) {
				var grabbed = this.grabbed;
				if ( unitInfo.nation  == this.ressources.game.youAre ) {
					if ( !grabbed.origin || grabbed.origin == unitInfo.zone ) {
						grabbed.origin = unitInfo.zone;
						grabbed.units.push(unitInfo);
						if (!--this.ressources.game.zones[grabbed.origin][this.ressources.game.youAre][unitInfo.unit]) {
							this.selectedUnit = false;
						}
					} 
				}
			}
		},
		dropUnit: function () {
			var grabbed = this.grabbed;
			if ( grabbed.origin ) {
				var unitInfo = grabbed.units.pop();
				this.ressources.game.zones[grabbed.origin][this.ressources.game.youAre][unitInfo.unit]++;
				if (!grabbed.units.length) {
					grabbed.origin = false;
				}
				return unitInfo;
			}
		},
		startPanning: function (x, y) {
			this.panning = true;
			this.panningStart.x = x;
			this.panningStart.y = y;
			this.viewportOffsetPanningStart.x = this.viewportOffset.x;
			this.viewportOffsetPanningStart.y = this.viewportOffset.y;
		},
		stopPanning: function(x, y) {
			this.panning = false;
		},
		pan: function(x, y) {
			var viewportOffset = this.viewportOffset;
			viewportOffset.x = this.viewportOffsetPanningStart.x - ( x - this.panningStart.x );
			viewportOffset.y = this.viewportOffsetPanningStart.y - ( y - this.panningStart.y );
			if ( viewportOffset.y < 0 ) {
				viewportOffset.y = 0;
			}
			if( viewportOffset.y > (this.bufferContext.canvas.height - this.viewportContext.canvas.height) ) {
				viewportOffset.y = this.bufferContext.canvas.height - this.viewportContext.canvas.height;
			}
			if( viewportOffset.x < 0 ) {
				viewportOffset.x = BOARD_WIDTH;
				this.startPanning( x, y );
			}
			if( viewportOffset.x > BOARD_WIDTH ) {
				viewportOffset.x = 0;
				this.startPanning( x, y );
			}	
		},
		setup: function( viewportContext ) {
			var bufferCanvas = document.createElement('canvas');
			this.boardBuffer = new Image ( );
			bufferCanvas.width = BOARD_WIDTH;
			bufferCanvas.height = BOARD_HEIGHT;
			this.bufferContext = bufferCanvas.getContext('2d');
			this.viewportContext = viewportContext;
			this.isCooledDown = true;
			var that = this;
	
			jQuery(this.viewportContext.canvas).mousedown( function( e ) {
				switch( e.which ) {
					case 3:
						that.startPanning( e.screenX, e.screenY );
				}
			});
			
			jQuery(this.viewportContext.canvas).mouseup( function( e ) {
				switch( e.which ) {
					case 3:
						that.stopPanning( );			
				}
			});
			
			jQuery(this.viewportContext.canvas).mousemove( function( e ) {
				if (that.panning) {
					that.pan( e.screenX, e.screenY );
					that.swapBuffers();
				} else {
					if (that.isCooledDown) {
						that.isCooledDown = false;
						if ( that.selectedZone ) {
							var dirtyRect = expandRectangle(that.ressources.polygons[that.selectedZone].boundingbox, SELECT_GLOW_AMOUNT);
							that.clearBoard( dirtyRect );
							that.drawUnits( dirtyRect );
						}
						that.selectZone( e.pageX, e.pageY );
						that.selectUnit( e.pageX, e.pageY );
						that.drawInterface( );
						that.swapBuffers( );
						setTimeout(function() { that.isCooledDown = true; }, 50);
					}
				}
			});
			
			jQuery(this.viewportContext.canvas).click( function( e ) {
				switch( e.which ) {
					case 1:
						var unitInfo;
						if (unitInfo = that.getUnitAt( e.pageX, e.pageY )) {
							
							that.grabUnit( unitInfo );
							
							var placeRect = expandRectangle( that.getPlaceRect( unitInfo.zone, unitInfo.place ), UNIT_SHADOW + 10 ) ;
							that.clearBoard( placeRect );
							that.drawUnits( placeRect );
							that.drawInterface( placeRect );
							that.swapBuffers( );
						}	
				}
			});
			
			jQuery(this.viewportContext.canvas).bind( 'contextmenu', function( e ) {
				switch( e.which ) {
				case 3:
					var unitInfo = that.dropUnit();
					if (unitInfo) {
						var placeRect = expandRectangle( that.getPlaceRect( unitInfo.zone, unitInfo.place ), UNIT_SHADOW + 10 ) ;
						that.clearBoard( placeRect );
						that.drawUnits( placeRect );
						that.drawInterface( placeRect );
						that.swapBuffers( );
					}	
			}
				return false;
			});
			
			this.drawBoard( true );
			this.drawUnits( );	
			this.swapBuffers();
		}
	};

} ();

