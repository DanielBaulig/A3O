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
	};
	
/*	var createConvexHull = function( points ) {
		points.sort( function(e1, e2) {
			return e1[0] - e2[0];
		} );
		var i = points.length;
		var hull = [ points[--i] ], top = [ points[--i] ], hullstack;
		
		do {
			var hlen = hull.length;
			if (top && hlen == top.length) {
				var hull_low = hull[hlen-1], top_low = top[0];
				
				for (var j = 1; j < hlen; j++ ) {
					var currentHull = hull[hlen-j-1];
					hull_low = hull_low[1] > currentHull[1] ? currentHull : hull_low;
					top_low
				}
				// vereinigen in hull;
				
				
				top = hullstack.pop();
			} else {
				hulstack.push(top);
				top = hull;
				hull = [ points[--i] ];		
			}
			
		} while ( i > 1 )
	};*/
	
	var createControlPoints = function( anchors ) {
		var controlPoints = [];
		var alen = anchors.length;
		
		if ( alen != 0 ) {
			// the first control point is our first anchor point
			controlPoints.push ( anchors[0].slice() );
		}		
		
		// now we build the other control points for all anchors
		// between the first and the alst anchor point (not directly
		// including the first and the last anchor point)
		--alen;
		for (var i = 1; i < alen; i++ ) {
			
			var previous = anchors[i-1], current = anchors[i], next = anchors[i+1];
			var currentX = current[0], currentY = current[1];
			var previousX = previous[0], previousY = previous[1];
			
			// build vector from previous point to current point
			var vectorX = currentX - previousX; 
			var vectorY = currentY - previousY;
			// get length of the vector
			var firstEdgeLength = Math.sqrt( vectorX * vectorX + vectorY * vectorY); 
			// reduce vector to half length
			vectorX = vectorX / 2;
			vectorY = vectorY / 2;
			// get point A as middle point of the edge between
			// previous and current point
			var firstAX = previousX + vectorX
			var firstAY = previousY + vectorY;
			
			// repeat this for current and next point
			vectorX = next[0] - currentX;
			vectorY = next[1] - currentY;
			
			var secondEdgeLength = Math.sqrt( vectorX * vectorX + vectorY * vectorY );
			
			vectorX = vectorX / 2;
			vectorY = vectorY / 2;
			
			var secondAX = currentX + vectorX;
			var secondAY = currentY + vectorY;
			
			// now build a vector from firstA to secondA. along this edge
			// we will find our point B.
			// where point B is placed along this vector equals the 
			// ratio of firstEdgeLength and secondEdgeLength. 
			
			var ratio = firstEdgeLength / (firstEdgeLength + secondEdgeLength);
			
			vectorX = (secondAX - firstAX) * ratio + firstAX;
			vectorY = (secondAY - firstAY) * ratio + firstAY;
			
			// so we got our point B. next we will create the vector that 
			// points from B to the current point and move our A points
			// along that vector. the moved A points are our control points!
			vectorX = currentX - vectorX;
			vectorY = currentY - vectorY;
			
			// save our control points :)
			controlPoints.push( [firstAX + vectorX, firstAY + vectorY] );
			controlPoints.push( [secondAX + vectorX, secondAY + vectorY] );
		};
		
		if (alen > 0) { 
			// note that alen was reduced by 1 at the beginning of the 
			// for loop, so alen > 0 actually means "if there are at least
			// 2 elements present" - and not one.
			// i should point to the last element after the loop.
			
			// the last control point is our last anchor point
			controlPoints.push ( anchors[i] );
		};
		
		return controlPoints;
	};
	
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
	};
	
	// Expands a rectangle by expand pixels in each direction
	var expandRectangle = function ( rectangle, expand ) {
		var ul = rectangle.ul, lr = rectangle.lr;
		return { ul: [ ul[0]-expand, ul[1]-expand ], lr: [ lr[0]+expand, lr[1]+expand ] };
	};
	
	
	// A3O GAME OBJECT
	return {
		bezierControlPoints: [],
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
		panned: false,
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
			var zones = ressources.match.zones;
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
			bufferContext.strokeStyle = 'black';
			bufferContext.font = 'bold 10pt Sans-serif';
			
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
							if (unit > 1) {
								
								bufferContext.beginPath();
								bufferContext.strokeText( unit, place[0] + Math.floor(UNIT_WIDTH/4), place[1] + UNIT_HEIGHT );
								bufferContext.closePath();
							
								bufferContext.fillText( unit, place[0] + Math.floor(UNIT_WIDTH/4), place[1] + UNIT_HEIGHT );
							}
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
			/*if ( selectedZone ) {
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
			}*/			
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
		drawGrabbedUnits: function ( x, y ) {
			var viewportContext = this.viewportContext;
			var coords = jQuery(viewportContext.canvas).offset();
			x = x - coords.left;
			y = y - coords.top;
			var grabbed = this.grabbed;
			
			if ( grabbed.origin ) {
				var units = grabbed.units;
				var ulen = units.length;
				for (var i = 0; i < ulen; i++ ) {
					var unit = units[i];
					var image = this.ressources.sprites[unit.nation][unit.unit];
					viewportContext.drawImage( image, x + UNIT_WIDTH * i, y + UNIT_HEIGHT * i, UNIT_WIDTH, UNIT_HEIGHT );
				}
			}

		},
		drawTargetSeeker: function( start, end ) {
			
		},
		_drawBezierStrip: function( anchors, controlpoints, context ) {
			var alen = anchors.length, clen = controlpoints.length;
			
			
			if (alen * 2 - 2 != clen)
			{
				throw "number of anchorpoints does not suite number of controlpoints";
			}
			
			var anchor = anchors[0];
			
			context.beginPath();
			context.moveTo( anchor[0], anchor[1] );
			
			for (var i = 1; i < alen; i++ ) 
			{
				anchor = anchors[i]; 
				var c = i * 2;
				var cp1 = controlpoints[c - 2], cp2 = controlpoints[c - 1];
				
				context.bezierCurveTo( cp1[0], cp1[1], cp2[0], cp2[1], anchor[0], anchor[1] );
			}
			context.stroke();
		},
		drawPathIndicator: function ( path ) {
			var zone, plen = path.length, anchors = [], aX, aY;
			var viewportContext = this.viewportContext, viewportOffset = this.viewportOffset;
			
			viewportContext.save();
			viewportContext.lineWidth = 10;
			viewportContext.strokeStyle = 'red';
			for (var i = 0; i < plen; i++ ) {
				zone = this.ressources.polygons[path[i]].center;
				aX = zone[0] - viewportOffset.x;
				aY = zone[1] - viewportOffset.y;
				
				anchors.push( [aX, aY] );
			}
			this._drawBezierStrip( anchors, createControlPoints(anchors), viewportContext );
			viewportContext.restore();
		},
		/*drawBezierStrip: function( anchors ) {
			// we'll first check if our bezier strip passes the "wrapping edge"
			// where the ends of our world map are wrapped around like on a 
			// torus. if we do pass the wrapping edge we will have to handle
			// this gap specially, or else the strip will be built actually not
			// crossing the wrapping edge, but span all around the world in the
			// other direction what is no what we want.
			var alen = anchors.length;
			
			if (alen < 3) {
				// we want at least 3 points. If there are only 2 points then
				// we should not draw the bezier strip, but the "target pointer"
				// which basicly is an arrow.
				return;
			}
			
			var halfBoard = BOARD_WIDTH / 2;
			for (var i = 1; i < alen; i++) {
				if ( Math.abs(anchors[i][0] - anchors[i-1][0]) > halfBoard ) {
					// this is a huge gap! the shortest way connecting those 
					// anchors is by passing the wrapping edge. we will handle 
					// the edge by cutting our stip into two sub-strips. then we 
					// will copy the last element of the first sub-strip and put 
					// it at the beginning of the second sub-strip. of the scond 
					// sub-strip we will copy the first element and put it at the 
					// end of the first sub-strip.
					// then we will substract BOARD_WIDTH of the bigger of the
					// two just copied elements and add BOARD_WIDTH to the 
					// smaller of the two just copied elements.
					// this will create two strips that share a single anchor point 
					// in a "wrapped" world. One of the strips will "overdraw" on
					// the left edge and the other will "overdraw" on the right 
					// edge which will lead to a connected strip, if both edges
					// are drawn next to each other.
					// but lets get started :)
					
					var firstStrip = anchors.slice(0, i);
					// make a copy of the array in firstStrip[i-1]
					var lastElement = firstStrip[i-1].slice();
					var secondStrip = anchors.slice(i);
					// make a copy of the array in secondStrip[0]
					var firstElement = secondStrip[0].slice();
					
					if (firstElement[0] > lastElement[0] ) {
						firstElement[0] -= BOARD_WIDTH;
						lastElement[0] += BOARD_WIDTH;
					} else {
						firstElement[0] += BOARD_WIDTH;
						lastElement[0] -= BOARD_WIDTH;
					}
						
					
					firstStrip.push(firstElement);
					secondStrip.unshift(lastElement);
					
					// get the controlpoints fot our substrips
					var firstStripControlpoints = createControlPoints( firstStrip );
					var secondStripControlpoints = createControlPoints ( secondStrip );
					
					// the last controlpoint in our firstStripControlpoints array
					// and the first controlpoint in our secondStripControlpoints
					// are equivalent to their respective first and last anchor
					// points. This is fine if drawing a dedicated bezier strip, BUT
					// it won't let our two strips blend nicely.
					// what we need to do is to grab the second last control point
					// of our first strip and make it the first control point in
					// our second strip and take the second control point in our
					// second strip and make it the last controlpoint in our first 
					// strip. basicly redoing what we have done for the anchor
					// points with the control points but this time not appending them
					// but overriding them.
					var firstCPLength = firstStripControlpoints.length;
					firstStripControlpoints[firstCPLength-1] = secondStripControlpoints[1].slice();
					secondStripControlpoints[0] = firstStripControlpoints[firstCPLength-2].slice();
					
					if ( firstStripControlpoints[firstCPLength-1] > secondStripControlpoints[0] ) {
						firstStripControlpoints[firstCPLength-1][0] -= BOARD_WIDTH;
						secondStripControlpoints[0][0] += BOARD_WIDTH;
					} else {
						firstStripControlpoints[firstCPLength-1][0] += BOARD_WIDTH;
						secondStripControlpoints[0][0] -= BOARD_WIDTH;
					}
					
					this._drawBezierStrip( firstStrip, firstStripControlpoints );
					this._drawBezierStrip( secondStrip, secondStripControlpoints );
					return [ firstStripControlpoints, secondStripControlpoints ];
				}
			}
			var cp = createControlPoints( anchors );
			this._drawBezierStrip( anchors, cp );
			return [ cp ];
		},*/
		clearBezierStrip: function( controlpoints ) {
			var clen = controlpoints.length;
			var bufferContext = this.bufferContext;
			bufferContext.save();
			bufferContext.strokeStyle= 'red';
			bufferContext.beginPath();
			for (var i = 2; i < clen; i++ ) {
				var one = controlpoints[i-2], two = controlpoints[i-1], three = controlpoints[i];
				bufferContext.moveTo( one[0], one[1] );
				bufferContext.lineTo( two[0], two[1] );
				bufferContext.lineTo( three[0], three[1] );
			}
			bufferContext.lineTo( controlpoints[0][0], controlpoints[0][1] );
			bufferContext.clip();
			this.clearBoard();
			bufferContext.restore();
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
		loadRessources: function ( match, doneCallback ) {
			var delayedCallback = createCallback( 4, doneCallback );
			this.loadPolygons( delayedCallback );
			this.loadBackgroundImages( delayedCallback );
			this.loadSprites( delayedCallback );
			this.loadMatchData( match, delayedCallback );
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
		loadMatchData: function ( match, doneCallback ) {
			this.ressources.match = {
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
			var z = ressources.match.zones[zone];
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
		getPlaceOf: function( zone, unit, nation ) {
			var z = this.ressources.match.zones[zone];
			var counter = 0;
			
			for (var n in z) {
				var _n = z[n];
				for (var u in _n) {
					if ( u == unit && n == nation ) {
						return counter;
					} else {
						counter++;
					}
				}
			}
			return -1;
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
				if ( unitInfo.nation  == this.ressources.match.youAre ) {
					if ( !grabbed.origin || grabbed.origin == unitInfo.zone ) {
						grabbed.origin = unitInfo.zone;
						grabbed.units.push(unitInfo);
						if (!--this.ressources.match.zones[grabbed.origin][this.ressources.match.youAre][unitInfo.unit]) {
							this.selectedUnit = false;
						}
					} 
				}
			}
		},
		dropUnit: function ( zone ) {
			var grabbed = this.grabbed;
			if ( zone ) {
				var unitInfo = grabbed.units.pop();
				var ressources = this.ressources;
				var match = ressources.match;
				var zones = match.zones;
				
				if ( !zones[zone] ) {
					zones[zone] = {};
				}
				if ( !zones[zone][unitInfo.nation] ) {
					zones[zone][unitInfo.nation] = {};
				}
				if ( !zones[zone][unitInfo.nation][unitInfo.unit] ) {
					zones[zone][unitInfo.nation ][unitInfo.unit] = 0;
				}
				zones[zone][unitInfo.nation ][unitInfo.unit]++;
				if (!grabbed.units.length) {
					grabbed.origin = false;
				}
				unitInfo.zone = zone;
				unitInfo.place = this.getPlaceOf( zone, unitInfo.unit, unitInfo.nation  );
				return unitInfo;
			}
		},
		returnUnit: function () {
			return this.dropUnit(this.grabbed.origin);
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
				var x = e.pageX, y = e.pageY;
				switch( e.which ) {
					case 3:
						that.startPanning( x, y );
				}
			});
			
			jQuery(this.viewportContext.canvas).mouseup( function( e ) {
				switch( e.which ) {
					case 3:
						that.stopPanning( );			
				}
			});
			
			jQuery(this.viewportContext.canvas).mousemove( function( e ) {
				var x = e.pageX, y = e.pageY;
				if (that.panning) {
					that.panned = true;
					that.pan( x, y );
					that.swapBuffers();
					that.drawGrabbedUnits( x, y );
				} else {
					if (that.isCooledDown) {
						that.isCooledDown = false;
						if ( that.selectedZone ) {
							var dirtyRect = expandRectangle(that.ressources.polygons[that.selectedZone].boundingbox, SELECT_GLOW_AMOUNT);
							that.clearBoard( dirtyRect );
							that.drawUnits( dirtyRect );
						}
						
						that.selectZone( x, y );
						that.selectUnit( x, y );
						that.drawInterface( );
						that.swapBuffers( );
						if ( that.selectedZone && that.grabbed.origin ) {
							//that.drawPathIndicator( [ that.grabbed.origin, "EasternGermany", that.selectedZone ] );
						}
						that.drawGrabbedUnits( x, y );
						// prevent flooding the execution queue with high laod operations
						// by only allowing update on the canvas due mouse movement every
						// 25 milli seconds.
						setTimeout(function() { that.isCooledDown = true; }, 25);
					}
				}
			});
			
			jQuery(this.viewportContext.canvas).click( function( e ) {
				var x = e.pageX, y = e.pageY;
				switch( e.which ) {
					case 1:
						var unitInfo, zone;
						
						if ( that.grabbed.origin && that.grabbed.origin != (zone = that.getZoneAt( x, y ) ) ) {
							unitInfo = that.dropUnit( zone );
							var placeRect = expandRectangle( that.getPlaceRect( unitInfo.zone, unitInfo.place ), UNIT_SHADOW + 10 ) ;
							that.clearBoard( placeRect );
							that.drawUnits( placeRect );
							that.drawInterface( placeRect );
							that.swapBuffers( );
							that.drawGrabbedUnits( x, y );
						} else if (unitInfo = that.getUnitAt( x, y )) {
							
							that.grabUnit( unitInfo );
							
							var placeRect = expandRectangle( that.getPlaceRect( unitInfo.zone, unitInfo.place ), UNIT_SHADOW + 10 ) ;
							that.clearBoard( placeRect );
							that.drawUnits( placeRect );
							that.drawInterface( placeRect );
							that.swapBuffers( );
							that.drawGrabbedUnits( x, y );
						}
				}
			});
			
			jQuery(this.viewportContext.canvas).bind( 'contextmenu', function( e ) {
				var x = e.pageX, y = e.pageY;
				switch( e.which ) {
				case 3:
					if (!that.panned) {
						var unitInfo = that.returnUnit();
						if (unitInfo) {
							var placeRect = expandRectangle( that.getPlaceRect( unitInfo.zone, unitInfo.place ), UNIT_SHADOW + 10 ) ;
							that.clearBoard( placeRect );
							that.drawUnits( placeRect );
							that.drawInterface( placeRect );
							that.swapBuffers( );
							that.drawGrabbedUnits( x, y );
						}	
					}
			}
				that.panned = false;
				return false;
			});
			
			jQuery(this.viewportContext.canvas).dblclick(function() {
				return false;
			});
			
			this.drawBoard( true );
			this.drawUnits( );	
			this.swapBuffers();
		}
	};

} ();

